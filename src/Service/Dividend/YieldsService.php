<?php

namespace App\Service\Dividend;

use App\Contracts\Dividend\DividendTaxRateResolverInterface;
use App\Entity\Position;
use App\Enum\SortDirection;
use App\Enum\SortField;
use App\Util\Constants;
use App\Entity\Pie;
use App\Dto\PositionYield;
use App\Repository\PositionRepository;
use App\Repository\TransactionRepository;
use Symfony\Component\Stopwatch\Stopwatch;
use App\Decorator\Factory\AdjustedPositionDecoratorFactory;
use App\DataProvider\TransactionDataProvider;
use App\DataProvider\CorporateActionDataProvider;
use App\DataProvider\DividendDataProvider;
use Doctrine\Common\Collections\ArrayCollection;
use App\Contracts\ExchangeRate\DividendExchangeRateResolverInterface;

class YieldsService
{
    public function __construct(
        //private CacheInterface $pool,
        private Stopwatch $stopwatch,
        private PositionRepository $positionRepository,
        private TransactionRepository $transactionRepository,
        private DividendService $dividendService,
        private DividendExchangeRateResolverInterface $dividendExchangeRateResolver,
        private DividendTaxRateResolverInterface $dividendTaxRateResolver,
        private TransactionDataProvider $transactionDataProvider,
        private CorporateActionDataProvider $corporateActionDataProvider,
        private DividendDataProvider $dividendDataProvider,
        private AdjustedPositionDecoratorFactory $adjustedPositionDecoratorFactory,
    ) {
    }
    public function yield(
        string $sort = 'symbol',
        string $sortDirection = 'ASC',
        ?Pie $pie = null
    ): array {
        $positionRepository = $this->positionRepository;
        $this->stopwatch->start('yield-data', 'pie-yield');
        $positionYield = new PositionYield();
        $this->stopwatch->start('getting-positions-from-database', 'parsing');
        if ($pie) {
            $report = $this->transactionRepository->getSummaryByPie($pie);
            $positions = $positionRepository->getAllByIds(array_keys($report));
        } else {
            $positions = $positionRepository->getAllOpen($pie, null);
        }

        // A synchronised position can still be waiting for its ticker to be matched.
        $positions = array_values(array_filter(
            $positions,
            static fn(Position $position): bool => $position->getTicker() !== null
        ));

        $this->stopwatch->stop('getting-positions-from-database');

        $allocated = 0.0;

        $this->stopwatch->start('processing-file', 'parsing');

        $tickerList = array_map(function ($position) {
            return $position->getTicker();
        }, $positions);

        $transactions = $this->transactionDataProvider->load($positions);
        $actions = $this->corporateActionDataProvider->load($tickerList);
        $dividends = $this->dividendDataProvider->load($tickerList);

        $dividendService =  $this->dividendService->load(transactions: $transactions, corporateActions: $actions, dividends: $dividends);

        $totalDividend = 0.0;
        $totalNetYearlyDividend = 0.0;
        $totalNetYearlyDividendPerStock = 0.0;
        $totalNetMonthlyDividend = 0.0;
        $dividendYieldOnCost = 0.0;
        $totalAvgYield = 0.0;

        $this->adjustedPositionDecoratorFactory->load($transactions, $actions);

        /**
         * @var Position $position
         */
        foreach ($positions as $position) {
            $avgPrice =
                $pie && $report[$position->getId()]
                    ? $report[$position->getId()]['avgPrice']
                    : $position->getPrice();

            $amount = $position->getAmount();

            $positionDecorator = $this->adjustedPositionDecoratorFactory->decorate($position);
            $adjustedAmount = $positionDecorator->getAdjustedAmount();
            $adjustedAveragePrice = $positionDecorator->getAdjustedAveragePrice();

            $position->setAdjustedAmount($adjustedAmount);
            // Positions synchronised from the broker have no transactions to derive an amount from.
            $currentAmount = (float) (isset($transactions[$position->getId()]) ? $adjustedAmount : $amount);
            $position->setAdjustedAveragePrice($adjustedAveragePrice);

            $allocation =
                $pie && $report[$position->getId()]
                    ? $report[$position->getId()]['allocation']
                    : $position->getAllocation();
            $ticker = $position->getTicker();

            if ($allocation > 0) {
                $allocated += $allocation;
            }

            $lastCash = 0;
            $lastDividendDate = null;

            $numPayoutsPerYear = $ticker->getDividendMonths()->count();
            $calendarList = new ArrayCollection($dividends[$position->getTicker()->getId()] ?? []);

            // Calendars are loaded by ascending payment date.
            $latestCalendar = $calendarList->last();

            $netTotalForwardYearlyPayout = 0;
            $netForwardYearlyPayout = 0;
            $dividendYield = 0;
            $netTotalPayoutPerPaydate = 0;
            $lastCash = 0;
            $lastCashCurrency = '$';
            // Tax rate in percent; currency rules (e.g. UK) need a calendar entry
            $taxRate = $latestCalendar
                ? $this->dividendTaxRateResolver->getTaxRateForCalendar($latestCalendar) * 100
                : ($ticker->getTax() ? $ticker->getTax()->getTaxRate() * 100 : Constants::TAX);
            $exchangeRate = $latestCalendar
                ? $this->dividendExchangeRateResolver->getRateForCalendar($latestCalendar)
                : 0;

            if ($latestCalendar) {
//              $lastCash = $dividendService->getCashAmount($ticker);
                $lastCash = $dividendService->getCashAmount($ticker);

                $currency = $latestCalendar->getCurrency() ?? $ticker->getCurrency();
                $lastCashCurrency = html_entity_decode($currency?->getSign() ?? '$', ENT_QUOTES | ENT_HTML5);
                $lastDividendDate = $latestCalendar->getPaymentDate();

                $netTotalForwardYearlyPayout =
                    $numPayoutsPerYear *
                    $dividendService->getForwardNetDividend(
                        $position->getTicker(),
                        $currentAmount
                    );
                $netForwardYearlyPayout =
                    $numPayoutsPerYear *
                    $dividendService->getNetDividend(
                        $position,
                        $latestCalendar
                    );
                $dividendYield = $dividendService->getForwardNetDividendYield(
                    $position,
                    $position->getTicker(),
                    $currentAmount,
                    $allocation
                );
                $netTotalPayoutPerPaydate = 0;
                if ($numPayoutsPerYear > 0) {
                    $netTotalPayoutPerPaydate =
                        $netTotalForwardYearlyPayout / $numPayoutsPerYear;
                    $totalNetMonthlyDividend += $netTotalPayoutPerPaydate;
                }
            }
            $dividendPerYear = $numPayoutsPerYear * $lastCash;

            // A ticker can have several open positions; each gets its own bar.
            $tickerLabel = $ticker->getSymbol() . '#' . $position->getId();
            $positionYield->labels[$tickerLabel] = sprintf(
                '%s (%s)',
                mb_substr(str_replace(["'", '"'], '', (string) $ticker->getFullname()), 0, 8),
                $ticker->getSymbol()
            );
            $positionYield->data[$tickerLabel] = $dividendYield;

            $positionYield->dataSource[] = [
                'ticker' => $ticker->getSymbol(),
                'tickerId' => $ticker->getId(),
                'position' => $position,
                'label' => $ticker->getFullname(),
                'yield' => $dividendYield,
                'payout' => $dividendPerYear,
                'payoutInEuro' => $dividendPerYear * $exchangeRate,
                'allocation' => $allocation,
                'netTotalPayoutPerPaydate' => $netTotalPayoutPerPaydate,
                'netForwardYearlyPayout' => $netForwardYearlyPayout,
                'netTotalForwardYearlyPayout' => $netTotalForwardYearlyPayout,
                'avgPrice' => $avgPrice,
                'lastDividend' => $lastCash,
                'lastDividendCurrency' => $lastCashCurrency,
                'lastDividendDate' => $lastDividendDate,
                'numPayoutsPerYear' => $numPayoutsPerYear,
                'amount' => $currentAmount,
                'taxRate' => $taxRate,
                'exchangeRate' => $exchangeRate,
            ];
            if ($allocation > 0) {
                $totalNetYearlyDividend += $netTotalForwardYearlyPayout;
            }
            $positionYield->sumAvgPrice += $avgPrice;
            $positionYield->sumDividends += $dividendPerYear;
            $totalDividend += $dividendPerYear * $currentAmount;
            $totalNetYearlyDividendPerStock += $netForwardYearlyPayout;
            $this->stopwatch->lap('processing-file');
        }
        $this->stopwatch->stop('processing-file');

        if ($positionYield->sumAvgPrice) {
            $totalAvgYield =
                ($positionYield->sumDividends / $positionYield->sumAvgPrice) *
                100;
        }
        if ($allocated) {
            $dividendYieldOnCost = ($totalNetYearlyDividend / $allocated) * 100;
        }
        $yieldData = $positionYield;

        //return $positionYield;
        //});

        ksort($yieldData->labels);
        ksort($yieldData->data);

        $sortField = SortField::fromString($sort);
        $direction = SortDirection::fromString($sortDirection);
        $dataSource = $this->sortRows($yieldData->dataSource, $sortField, $direction);

        //$this->pool->delete($poolKey);

        return [
            'data' => array_values($yieldData->data),
            'labels' => array_values($yieldData->labels),
            'datasource' => $dataSource,
            'sort' => $sortField->toString(),
            'sortDirection' => $direction->toString(),
            'totalAvgYield' => $totalAvgYield,
            'dividendYieldOnCost' => $dividendYieldOnCost,
            'allocated' => $allocated,
            'totalDividend' => $totalDividend,
            'totalNetYearlyDividend' => $totalNetYearlyDividend,
            'totalNetMonthlyDividend' => $totalNetMonthlyDividend,
            'totalNetYearlyDividendPerStock' => $totalNetYearlyDividendPerStock,
        ];
    }

    /**
     * Sorts on the numeric value; rows with the same value stay ordered by symbol.
     *
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    private function sortRows(array $rows, SortField $sortField, SortDirection $direction): array
    {
        $key = match ($sortField) {
            SortField::SYMBOL => 'ticker',
            SortField::DIVIDEND => 'payoutInEuro',
            SortField::YIELD => 'yield',
        };

        usort($rows, static function (array $a, array $b) use ($key, $direction): int {
            $order = $direction === SortDirection::DESC ? $b[$key] <=> $a[$key] : $a[$key] <=> $b[$key];

            return $order ?: $a['ticker'] <=> $b['ticker'];
        });

        return $rows;
    }
}
