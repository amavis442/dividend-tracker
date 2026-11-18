<?php

declare(strict_types=1);

namespace App\Service\IncomesShares;

use App\Contracts\Dividend\DividendServiceInterface;
use App\Contracts\Dividend\DividendTaxRateResolverInterface;
use App\Contracts\ExchangeRate\DividendExchangeRateResolverInterface;
use App\DataProvider\CorporateActionDataProvider;
use App\DataProvider\TransactionDataProvider;
use App\Decorator\Factory\AdjustedPositionDecoratorFactory;
use App\Entity\CorporateAction;
use App\Entity\IncomesSharesData;
use App\Entity\IncomesSharesDataSet;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Exception\ExchangeRateUnavailableException;
use App\Helper\CorporateActionRatio;
use App\Repository\IncomesSharesDataRepository;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Service\Dividend\DividendAdjuster;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Calculates IncomesShares datasets (returns per share and totals) and stores them.
 *
 * The calculate methods return rows keyed by ISIN plus the totals that are
 * shown next to them.
 *
 * @phpstan-type CalculationResult array{
 *     rows: array<string, array<string, mixed>>,
 *     totalAllocation: float,
 *     totalDistribution: float,
 *     totalProfitLoss: float,
 *     totalExpectedDistribution: ?float,
 *     yield: float
 * }
 */
final class IncomesSharesDataSetService
{
    private bool $rateProviderUnavailable = false;

    public function __construct(
        private readonly PositionRepository $positionRepository,
        private readonly PaymentRepository $paymentRepository,
        private readonly TickerRepository $tickerRepository,
        private readonly IncomesSharesDataRepository $incomesSharesDataRepository,
        private readonly DividendServiceInterface $dividendService,
        private readonly DividendExchangeRateResolverInterface $exchangeRateResolver,
        private readonly DividendTaxRateResolverInterface $taxRateResolver,
        private readonly CorporateActionDataProvider $corporateActionDataProvider,
        private readonly TransactionDataProvider $transactionDataProvider,
        private readonly AdjustedPositionDecoratorFactory $adjustedPositionDecoratorFactory,
        private readonly DividendAdjuster $dividendAdjuster,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Adds an empty share (price and profit/loss 0) for every listed ticker with an open position.
     *
     * @param string[] $isins
     */
    public function addSharesForIsins(IncomesSharesDataSet $dataSet, array $isins): void
    {
        $tickers = $this->tickerRepository->findBy(['isin' => $isins], ['fullname' => 'ASC']);

        $heldTickerIds = [];
        foreach ($this->positionRepository->findBy(['ticker' => $tickers, 'closed' => false]) as $position) {
            $heldTickerIds[$position->getTicker()->getId()] = true;
        }

        foreach ($tickers as $ticker) {
            if (!isset($heldTickerIds[$ticker->getId()])) {
                continue;
            }

            $share = new IncomesSharesData();
            $share->setTicker($ticker);
            $share->setPrice(0);
            $share->setProfitLoss(0);
            // Keyed by ticker, so submitted values can never end up on another ticker.
            $dataSet->getShares()->set($ticker->getId(), $share);
        }
    }

    /**
     * Calculates a new dataset from the entered prices/profit-loss and the
     * current open positions. With $persist the shares and dataset are stored;
     * without it the result also holds the expected distributions for display.
     *
     * @return CalculationResult
     */
    public function calculateFromPositions(IncomesSharesDataSet $dataSet, bool $persist): array
    {
        $uuid = Uuid::v7();
        $dataSet->setUuid($uuid);

        $actions = $persist ? null : $this->corporateActionsFor($dataSet->getShares()->toArray());
        $result = $this->emptyResult();
        foreach ($dataSet->getShares() as $share) {
            $ticker = $share->getTicker();
            $position = $this->positionRepository->findOneBy([
                'ticker' => $ticker->getId(),
                'closed' => false,
            ]);
            $allocation = $position->getAllocation();
            $distributions = $this->paymentRepository->getSumDividendsByPosition($position);
            $amount = $this->currentAmount($position);

            $this->addToResult(
                $result,
                $ticker,
                $allocation,
                $amount,
                $share->getPrice(),
                $distributions,
                $share->getProfitLoss(),
                $actions
            );

            if ($persist) {
                $share->setPosition($position);
                $share->setDistributions($distributions);
                $share->setAllocation($allocation);
                $share->setAmount($amount);
                $this->stampShare($share, $dataSet, $uuid);
                $this->entityManager->persist($share);
            }
        }

        $this->applyTotals($dataSet, $result);

        if ($persist) {
            $this->entityManager->persist($dataSet);
            $this->entityManager->flush();
        }

        return $result;
    }

    /**
     * Recalculates an existing dataset from its stored shares. With $persist
     * the changes are stored; without it the result also holds the expected
     * distributions for display.
     *
     * @return CalculationResult
     */
    public function recalculate(IncomesSharesDataSet $dataSet, bool $persist): array
    {
        $actions = $persist ? null : $this->corporateActionsFor($dataSet->getShares()->toArray());
        $result = $this->emptyResult();
        foreach ($dataSet->getShares() as $share) {
            $this->addToResult(
                $result,
                $share->getTicker(),
                $share->getAllocation(),
                $share->getAmount(),
                $share->getPrice(),
                $share->getDistributions(),
                $share->getProfitLoss(),
                $actions,
                $share->getCreatedAt()
            );

            if ($persist) {
                $this->entityManager->persist($share);
            }
        }

        $this->applyTotals($dataSet, $result);

        if ($persist) {
            $this->entityManager->persist($dataSet);
            $this->entityManager->flush();
        }

        return $result;
    }

    /**
     * Rows of a stored dataset, based on its stored share amounts, including
     * the expected next net distribution in euro.
     *
     * @return array{rows: array<string, array<string, mixed>>, totalExpectedDistribution: ?float}
     */
    public function rowsForDisplay(IncomesSharesDataSet $dataSet): array
    {
        $shares = $this->incomesSharesDataRepository->findByDataset($dataSet->getUuid());
        $actions = $this->corporateActionsFor($shares);

        $rows = [];
        foreach ($shares as $share) {
            $ticker = $share->getTicker();
            $amount = $share->getAmount();

            $rows[$ticker->getIsin()] = $this->row(
                $ticker,
                $share->getAllocation(),
                $amount,
                $share->getPrice(),
                $share->getDistributions(),
                $share->getProfitLoss()
            ) + [
                'expectedDistribution' => $this->expectedDistribution(
                    $ticker,
                    $amount,
                    $actions,
                    $share->getCreatedAt()
                ),
            ];
        }

        return ['rows' => $rows, 'totalExpectedDistribution' => $this->totalExpectedDistribution($rows)];
    }

    /**
     * Stores a new dataset from parsed export data (see IncomesSharesCsvParser).
     * Symbols without a ticker or an open position are skipped and reported;
     * nothing is stored when no symbol can be used.
     *
     * @param array<string, array{price: float, result: float}> $exportData
     *
     * @return array{dataSet: ?IncomesSharesDataSet, skippedSymbols: list<string>}
     */
    public function importExport(array $exportData): array
    {
        $tickers = $this->tickerRepository->findBy(
            ['symbol' => array_keys($exportData)],
            ['fullname' => 'ASC']
        );

        // Ordered, so the newest position of a ticker is used.
        $positions = [];
        $openPositions = $this->positionRepository->findBy(['ticker' => $tickers, 'closed' => false], ['id' => 'ASC']);
        foreach ($openPositions as $position) {
            $positions[$position->getTicker()->getId()] = $position;
        }

        $uuid = Uuid::v7();
        $dataSet = new IncomesSharesDataSet();
        $result = $this->emptyResult();
        $importedSymbols = [];
        foreach ($tickers as $ticker) {
            $position = $positions[$ticker->getId()] ?? null;
            if ($position === null) {
                continue;
            }

            $importedSymbols[] = $ticker->getSymbol();
            $item = $exportData[$ticker->getSymbol()];
            $share = new IncomesSharesData();
            $share->setTicker($ticker);
            $share->setPrice($item['price']);
            $share->setProfitLoss($item['result']);
            $dataSet->getShares()->add($share);

            $distributions = $this->paymentRepository->getSumDividendsByPosition($position);

            $result['totalDistribution'] += $distributions;
            $result['totalAllocation'] += $position->getAllocation();
            $result['totalProfitLoss'] += $share->getProfitLoss();

            $share->setPosition($position);
            $share->setDistributions($distributions);
            $share->setAllocation($position->getAllocation());
            $share->setAmount($this->currentAmount($position));
            $this->stampShare($share, $dataSet, $uuid);
            $this->entityManager->persist($share);
        }

        $skippedSymbols = array_values(array_diff(array_map('strval', array_keys($exportData)), $importedSymbols));
        if ($importedSymbols === []) {
            return ['dataSet' => null, 'skippedSymbols' => $skippedSymbols];
        }

        $dataSet->setUuid($uuid);
        $this->applyTotals($dataSet, $result);

        $this->entityManager->persist($dataSet);
        $this->entityManager->flush();

        return ['dataSet' => $dataSet, 'skippedSymbols' => $skippedSymbols];
    }

    /**
     * @return CalculationResult
     */
    public function emptyResult(): array
    {
        return [
            'rows' => [],
            'totalAllocation' => 0.0,
            'totalDistribution' => 0.0,
            'totalProfitLoss' => 0.0,
            'totalExpectedDistribution' => 0.0,
            'yield' => 0.0,
        ];
    }

    /**
     * @param IncomesSharesData[] $shares
     *
     * @return array<int, array<int, CorporateAction>> actions that took place, keyed by ticker id
     */
    private function corporateActionsFor(array $shares): array
    {
        $this->rateProviderUnavailable = false;

        $tickers = [];
        foreach ($shares as $share) {
            $ticker = $share->getTicker();
            $tickers[$ticker->getId()] = $ticker;
        }

        $now = new \DateTimeImmutable();

        return array_map(
            static fn(array $actions): array => array_values(array_filter(
                $actions,
                static fn(CorporateAction $action): bool => $action->getEventDate() <= $now
            )),
            $this->corporateActionDataProvider->load(array_values($tickers))
        );
    }

    /**
     * Sum of the expected distributions; null when one of them is unknown.
     *
     * @param array<string, array<string, mixed>> $rows
     */
    private function totalExpectedDistribution(array $rows): ?float
    {
        $distributions = array_column($rows, 'expectedDistribution');

        return in_array(null, $distributions, true) ? null : (float) array_sum($distributions);
    }

    /**
     * Net latest regular dividend for the shares in euro: 0 without a regular
     * dividend, null when it cannot be determined. A stored amount is brought
     * in line with the dividend by applying the actions since $amountDate.
     *
     * @param array<int, array<int, CorporateAction>> $actions keyed by ticker id
     */
    private function expectedDistribution(
        Ticker $ticker,
        ?float $amount,
        array $actions,
        ?\DateTimeImmutable $amountDate = null
    ): ?float {
        $calendar = $this->dividendService->getRegularCalendar($ticker);
        if ($calendar === null) {
            return 0.0;
        }

        if ($this->rateProviderUnavailable && $calendar->getCurrency()?->getSymbol() !== 'EUR') {
            return null;
        }

        $tickerActions = $actions[$ticker->getId()] ?? [];

        try {
            $cashAmount = $this->dividendAdjuster->getAdjustedDividend($calendar, $tickerActions);
            $exchangeRate = $this->exchangeRateResolver->getRateForCalendar($calendar);
            $taxRate = $this->taxRateResolver->getTaxRateForCalendar($calendar);
            $currentAmount = $this->amountAfterActions($amount, $tickerActions, $amountDate);
        } catch (\Throwable $exception) {
            $this->rateProviderUnavailable = $this->rateProviderUnavailable
                || ($exception instanceof ExchangeRateUnavailableException && $exception->providerFailed);
            $this->logger->warning('Expected distribution unavailable for {ticker}: {message}', [
                'ticker' => $ticker->getSymbol(),
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        return $cashAmount * $currentAmount * $exchangeRate * (1 - $taxRate);
    }

    private function currentAmount(Position $position): float
    {
        $this->adjustedPositionDecoratorFactory->load(
            $this->transactionDataProvider->load([$position]),
            $this->corporateActionDataProvider->load([$position->getTicker()])
        );

        return $this->adjustedPositionDecoratorFactory->decorate($position)->getAdjustedAmount();
    }

    /**
     * @param CorporateAction[] $actions
     */
    private function amountAfterActions(?float $amount, array $actions, ?\DateTimeImmutable $amountDate): ?float
    {
        if ($amountDate === null) {
            return $amount;
        }

        foreach ($actions as $action) {
            if ($action->getEventDate() > $amountDate) {
                $amount *= CorporateActionRatio::of($action);
            }
        }

        return $amount;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(
        Ticker $ticker,
        ?float $allocation,
        ?float $amount,
        ?float $price,
        ?float $distributions,
        ?float $profitLoss,
    ): array {
        $totalReturn = $allocation + $distributions + $profitLoss;
        $totalGain = $totalReturn - $allocation;

        return [
            'fullname' => $ticker->getFullname(),
            'allocation' => $allocation,
            'amount' => $amount,
            'price' => $price,
            'calcGain' => $price * $amount - $allocation,
            'distributions' => $distributions,
            'pl' => $profitLoss,
            'totalGain' => $totalGain,
            'totalReturn' => $totalReturn,
            'totalReturnPercentage' => $allocation > 0 ? ($totalGain / $allocation) * 100 : 0.0,
        ];
    }

    /**
     * @param array<int, array<int, CorporateAction>>|null $actions null when the
     *        expected distribution is not needed
     * @param \DateTimeImmutable|null $amountDate when a stored amount was recorded
     */
    private function addToResult(
        array &$result,
        Ticker $ticker,
        ?float $allocation,
        ?float $amount,
        ?float $price,
        ?float $distributions,
        ?float $profitLoss,
        ?array $actions,
        ?\DateTimeImmutable $amountDate = null,
    ): void {
        $row = $this->row($ticker, $allocation, $amount, $price, $distributions, $profitLoss);
        if ($actions !== null) {
            $row['expectedDistribution'] = $this->expectedDistribution($ticker, $amount, $actions, $amountDate);
        }

        $result['rows'][$ticker->getIsin()] = $row;
        $result['totalDistribution'] += $distributions;
        $result['totalAllocation'] += $allocation;
        $result['totalProfitLoss'] += $profitLoss;
    }

    /**
     * Sets totals, yield (distributions / allocation in %) and the creation or update date.
     */
    private function applyTotals(IncomesSharesDataSet $dataSet, array &$result): void
    {
        $result['yield'] = 0.0;
        if ($result['totalAllocation'] > 0) {
            $result['yield'] = ($result['totalDistribution'] / $result['totalAllocation']) * 100;
        }

        $result['totalExpectedDistribution'] = $this->totalExpectedDistribution($result['rows']);

        $dataSet->setTotalAllocation($result['totalAllocation']);
        $dataSet->setTotalDistribution($result['totalDistribution']);
        $dataSet->setTotalProfitLoss($result['totalProfitLoss']);
        $dataSet->setYield($result['yield']);

        if ($dataSet->getCreatedAt() === null) {
            $dataSet->setCreatedAt(new \DateTimeImmutable());
        } else {
            $dataSet->setUpdatedAt(new \DateTimeImmutable());
        }
    }

    private function stampShare(IncomesSharesData $share, IncomesSharesDataSet $dataSet, Uuid $uuid): void
    {
        $share->setDataset($uuid);
        $share->setCreatedAt(new \DateTimeImmutable());
        $share->setUpdatedAt(new \DateTimeImmutable());
        $share->setIncomesSharesDataSet($dataSet);
    }
}
