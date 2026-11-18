<?php

declare(strict_types=1);

namespace App\Service\IncomesShares;

use App\Contracts\Dividend\DividendServiceInterface;
use App\Contracts\Dividend\DividendTaxRateResolverInterface;
use App\Contracts\ExchangeRate\DividendExchangeRateResolverInterface;
use App\DataProvider\CorporateActionDataProvider;
use App\Entity\CorporateAction;
use App\Entity\IncomesSharesData;
use App\Entity\IncomesSharesDataSet;
use App\Entity\Ticker;
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
    public function __construct(
        private readonly PositionRepository $positionRepository,
        private readonly PaymentRepository $paymentRepository,
        private readonly TickerRepository $tickerRepository,
        private readonly IncomesSharesDataRepository $incomesSharesDataRepository,
        private readonly DividendServiceInterface $dividendService,
        private readonly DividendExchangeRateResolverInterface $exchangeRateResolver,
        private readonly DividendTaxRateResolverInterface $taxRateResolver,
        private readonly CorporateActionDataProvider $corporateActionDataProvider,
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

        foreach ($tickers as $ticker) {
            if ($this->positionRepository->findOneBy(['ticker' => $ticker->getId(), 'closed' => false]) === null) {
                continue;
            }

            $share = new IncomesSharesData();
            $share->setTicker($ticker);
            $share->setPrice(0);
            $share->setProfitLoss(0);
            $dataSet->getShares()->add($share);
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
            $amount = $position->getAmount();

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
                $actions
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
            ) + ['expectedDistribution' => $this->expectedDistribution($ticker, $amount, $actions)];
        }

        return ['rows' => $rows, 'totalExpectedDistribution' => $this->totalExpectedDistribution($rows)];
    }

    /**
     * Stores a new dataset from parsed export data (see IncomesSharesCsvParser).
     *
     * @param array<string, array{price: float, result: float}> $exportData
     */
    public function importExport(array $exportData): IncomesSharesDataSet
    {
        $tickers = $this->tickerRepository->findBy(
            ['symbol' => array_keys($exportData)],
            ['fullname' => 'ASC']
        );

        $uuid = Uuid::v7();
        $dataSet = new IncomesSharesDataSet();
        $result = $this->emptyResult();
        foreach ($tickers as $ticker) {
            $item = $exportData[$ticker->getSymbol()];
            $share = new IncomesSharesData();
            $share->setTicker($ticker);
            $share->setPrice($item['price']);
            $share->setProfitLoss($item['result']);
            $dataSet->getShares()->add($share);

            $position = $ticker->getPositions()[0];
            $distributions = $this->paymentRepository->getSumDividendsByPosition($position);

            $result['totalDistribution'] += $distributions;
            $result['totalAllocation'] += $position->getAllocation();
            $result['totalProfitLoss'] += $share->getProfitLoss();

            $share->setPosition($position);
            $share->setDistributions($distributions);
            $share->setAllocation($position->getAllocation());
            $share->setAmount($position->getAmount());
            $this->stampShare($share, $dataSet, $uuid);
            $this->entityManager->persist($share);
        }

        $dataSet->setUuid($uuid);
        $this->applyTotals($dataSet, $result);

        $this->entityManager->persist($dataSet);
        $this->entityManager->flush();

        return $dataSet;
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
     * @return array<int, array<int, CorporateAction>> keyed by ticker id
     */
    private function corporateActionsFor(array $shares): array
    {
        $tickers = [];
        foreach ($shares as $share) {
            $ticker = $share->getTicker();
            $tickers[$ticker->getId()] = $ticker;
        }

        return $this->corporateActionDataProvider->load(array_values($tickers));
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
     * dividend, null when the exchange or tax rate cannot be determined.
     *
     * @param array<int, array<int, CorporateAction>> $actions keyed by ticker id
     */
    private function expectedDistribution(Ticker $ticker, ?float $amount, array $actions): ?float
    {
        $calendar = $this->dividendService->getRegularCalendar($ticker);
        if ($calendar === null) {
            return 0.0;
        }

        $cashAmount = $this->dividendAdjuster->getAdjustedDividend($calendar, $actions[$ticker->getId()] ?? []);

        try {
            $exchangeRate = $this->exchangeRateResolver->getRateForCalendar($calendar);
            $taxRate = $this->taxRateResolver->getTaxRateForCalendar($calendar);
        } catch (\Throwable $exception) {
            $this->logger->warning('Expected distribution unavailable for {ticker}: {message}', [
                'ticker' => $ticker->getSymbol(),
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        return $cashAmount * $amount * $exchangeRate * (1 - $taxRate);
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
    ): void {
        $row = $this->row($ticker, $allocation, $amount, $price, $distributions, $profitLoss);
        if ($actions !== null) {
            $row['expectedDistribution'] = $this->expectedDistribution($ticker, $amount, $actions);
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
