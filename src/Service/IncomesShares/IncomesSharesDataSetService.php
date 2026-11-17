<?php

declare(strict_types=1);

namespace App\Service\IncomesShares;

use App\Contracts\ExchangeRate\ExchangeRateInterface;
use App\Entity\IncomesSharesData;
use App\Entity\IncomesSharesDataSet;
use App\Entity\Ticker;
use App\Repository\IncomesSharesDataRepository;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly ExchangeRateInterface $exchangeRateService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Adds an empty share (price and profit/loss 0) for every ticker whose ISIN is listed.
     *
     * @param string[] $isins
     */
    public function addSharesForIsins(IncomesSharesDataSet $dataSet, array $isins): void
    {
        $tickers = $this->tickerRepository->findBy(['isin' => $isins], ['fullname' => 'ASC']);

        foreach ($tickers as $ticker) {
            $share = new IncomesSharesData();
            $share->setTicker($ticker);
            $share->setPrice(0);
            $share->setProfitLoss(0);
            $dataSet->getShares()->add($share);
        }
    }

    /**
     * Calculates a new dataset from the entered prices/profit-loss and the
     * current open positions; with $persist the shares and dataset are stored.
     *
     * @return CalculationResult
     */
    public function calculateFromPositions(IncomesSharesDataSet $dataSet, bool $persist): array
    {
        $uuid = Uuid::v7();
        $dataSet->setUuid($uuid);

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
                $share->getProfitLoss()
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
     * Recalculates an existing dataset from its stored shares; with $persist
     * the changes are stored.
     *
     * @return CalculationResult
     */
    public function recalculate(IncomesSharesDataSet $dataSet, bool $persist): array
    {
        $result = $this->emptyResult();
        foreach ($dataSet->getShares() as $share) {
            $this->addToResult(
                $result,
                $share->getTicker(),
                $share->getAllocation(),
                $share->getAmount(),
                $share->getPrice(),
                $share->getDistributions(),
                $share->getProfitLoss()
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
     * Rows of a stored dataset including the expected next distribution in euro.
     *
     * @return array{rows: array<string, array<string, mixed>>, totalExpectedDistribution: float}
     */
    public function rowsForDisplay(IncomesSharesDataSet $dataSet): array
    {
        $rates = $this->exchangeRateService->getRates();
        $usdRate = $rates['USD'] ?: 1;
        $usdToEuro = 1 / $usdRate;

        $rows = [];
        $totalExpectedDistribution = 0.0;
        foreach ($this->incomesSharesDataRepository->findByDataset($dataSet->getUuid()) as $share) {
            $ticker = $share->getTicker();
            $amount = $share->getPosition()->getAmount();
            $nextCashAmount = $ticker->getCalendars()->first()->getCashAmount();
            $expectedDistribution = $nextCashAmount * $amount * $usdToEuro;

            $rows[$ticker->getIsin()] = $this->row(
                $ticker,
                $share->getAllocation(),
                $amount,
                $share->getPrice(),
                $share->getDistributions(),
                $share->getProfitLoss()
            ) + ['expectedDistribution' => $expectedDistribution];

            $totalExpectedDistribution += $expectedDistribution;
        }

        return ['rows' => $rows, 'totalExpectedDistribution' => $totalExpectedDistribution];
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
            'totalReturnPercentage' => ($totalGain / $allocation) * 100,
        ];
    }

    private function addToResult(
        array &$result,
        Ticker $ticker,
        ?float $allocation,
        ?float $amount,
        ?float $price,
        ?float $distributions,
        ?float $profitLoss,
    ): void {
        $result['rows'][$ticker->getIsin()] = $this->row(
            $ticker,
            $allocation,
            $amount,
            $price,
            $distributions,
            $profitLoss
        );
        $result['totalDistribution'] += $distributions;
        $result['totalAllocation'] += $allocation;
        $result['totalProfitLoss'] += $profitLoss;
    }

    /**
     * Sets totals, yield (distributions / allocation in %) and creation date on the dataset.
     */
    private function applyTotals(IncomesSharesDataSet $dataSet, array &$result): void
    {
        $result['yield'] = 0.0;
        if ($result['totalAllocation'] > 0) {
            $result['yield'] = ($result['totalDistribution'] / $result['totalAllocation']) * 100;
        }

        $dataSet->setTotalAllocation($result['totalAllocation']);
        $dataSet->setTotalDistribution($result['totalDistribution']);
        $dataSet->setTotalProfitLoss($result['totalProfitLoss']);
        $dataSet->setYield($result['yield']);
        $dataSet->setCreatedAt(new \DateTimeImmutable());
    }

    private function stampShare(IncomesSharesData $share, IncomesSharesDataSet $dataSet, Uuid $uuid): void
    {
        $share->setDataset($uuid);
        $share->setCreatedAt(new \DateTimeImmutable());
        $share->setUpdatedAt(new \DateTimeImmutable());
        $share->setIncomesSharesDataSet($dataSet);
    }

    /**
     * @return CalculationResult
     */
    private function emptyResult(): array
    {
        return [
            'rows' => [],
            'totalAllocation' => 0.0,
            'totalDistribution' => 0.0,
            'totalProfitLoss' => 0.0,
            'yield' => 0.0,
        ];
    }
}
