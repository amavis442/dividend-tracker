<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\IncomesShares;

use App\Contracts\Dividend\DividendServiceInterface;
use App\Contracts\Dividend\DividendTaxRateResolverInterface;
use App\Contracts\ExchangeRate\DividendExchangeRateResolverInterface;
use App\DataProvider\CorporateActionDataProvider;
use App\Entity\Calendar;
use App\Entity\CorporateAction;
use App\Entity\IncomesSharesData;
use App\Entity\IncomesSharesDataSet;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Exception\ExchangeRateUnavailableException;
use App\Repository\IncomesSharesDataRepository;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Service\Dividend\DividendAdjuster;
use App\Service\IncomesShares\IncomesSharesDataSetService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\Uid\Uuid;

final class IncomesSharesDataSetServiceTest extends TestCase
{
    private const ISIN = 'IE00TEST0001';
    private const TICKER_ID = 9;
    private const CASH_AMOUNT = 0.8;
    private const SHARES = 10.0;
    private const RATE = 0.5;
    private const TAX_RATE = 0.15;
    private const NET_EXPECTED = 3.4;

    public function testRecalculateAddsTheNetExpectedDistribution(): void
    {
        $result = $this->service()->recalculate($this->dataSet(), false);

        self::assertEqualsWithDelta(self::NET_EXPECTED, $result['rows'][self::ISIN]['expectedDistribution'], 0.0001);
        self::assertEqualsWithDelta(self::NET_EXPECTED, $result['totalExpectedDistribution'], 0.0001);
    }

    public function testCalculateFromPositionsAddsTheNetExpectedDistribution(): void
    {
        $result = $this->service()->calculateFromPositions($this->dataSet(), false);

        self::assertEqualsWithDelta(self::NET_EXPECTED, $result['rows'][self::ISIN]['expectedDistribution'], 0.0001);
    }

    public function testTheCashAmountIsAdjustedForCorporateActions(): void
    {
        $result = $this->service(actions: [self::TICKER_ID => [$this->split('-1 day')]])
            ->recalculate($this->dataSet(), false);

        self::assertEqualsWithDelta(
            self::NET_EXPECTED / 2,
            $result['rows'][self::ISIN]['expectedDistribution'],
            0.0001
        );
    }

    public function testAFutureCorporateActionIsIgnored(): void
    {
        $result = $this->service(actions: [self::TICKER_ID => [$this->split('+1 day')]])
            ->recalculate($this->dataSet(), false);

        self::assertEqualsWithDelta(
            self::NET_EXPECTED,
            $result['rows'][self::ISIN]['expectedDistribution'],
            0.0001
        );
    }

    public function testAStoredAmountFollowsALaterCorporateAction(): void
    {
        $share = $this->share();
        $share->setCreatedAt(new \DateTimeImmutable('-3 days'));
        $dataSet = new IncomesSharesDataSet();
        $dataSet->getShares()->add($share);

        $result = $this->service(actions: [self::TICKER_ID => [$this->split('-1 day')]])
            ->recalculate($dataSet, false);

        self::assertEqualsWithDelta(
            self::NET_EXPECTED,
            $result['rows'][self::ISIN]['expectedDistribution'],
            0.0001
        );
        self::assertSame(self::SHARES, $result['rows'][self::ISIN]['amount']);
    }

    public function testAnInvalidCorporateActionLeavesTheExpectedDistributionUnknown(): void
    {
        $result = $this->service(actions: [self::TICKER_ID => [$this->split('-1 day', 0.0)]])
            ->recalculate($this->dataSet(), false);

        self::assertNull($result['rows'][self::ISIN]['expectedDistribution']);
    }

    public function testAnUnreachableRateProviderIsTriedOnce(): void
    {
        $rates = $this->createMock(DividendExchangeRateResolverInterface::class);
        $rates->expects(self::once())->method('getRateForCalendar')
            ->willThrowException(new TransportException('Timeout'));
        $dataSet = new IncomesSharesDataSet();
        $dataSet->getShares()->add($this->share());
        $dataSet->getShares()->add($this->share(ticker: $this->ticker(10, 'IE00TEST0002')));

        $result = $this->service(rates: $rates)->recalculate($dataSet, false);

        self::assertNull($result['totalExpectedDistribution']);
    }

    public function testAMissingCurrencyRateDoesNotStopOtherTickers(): void
    {
        $rates = $this->createMock(DividendExchangeRateResolverInterface::class);
        $rates->expects(self::exactly(2))->method('getRateForCalendar')
            ->willThrowException(new ExchangeRateUnavailableException('No rate'));
        $dataSet = new IncomesSharesDataSet();
        $dataSet->getShares()->add($this->share());
        $dataSet->getShares()->add($this->share(ticker: $this->ticker(10, 'IE00TEST0002')));

        $this->service(rates: $rates)->recalculate($dataSet, false);
    }

    public function testImportUsesOpenPositionsAndReportsTheRest(): void
    {
        $tickers = $this->createConfiguredStub(TickerRepository::class, ['findBy' => [$this->ticker()]]);

        $import = $this->service(tickers: $tickers)->importExport([
            'TEST' => ['price' => 11.0, 'result' => 10.0],
            'UNKNOWN' => ['price' => 1.0, 'result' => 1.0],
        ]);

        self::assertNotNull($import['dataSet']);
        self::assertCount(1, $import['dataSet']->getShares());
        self::assertSame(['UNKNOWN'], $import['skippedSymbols']);
        self::assertSame(self::SHARES, $import['dataSet']->getShares()->first()->getAmount());
    }

    public function testImportStoresNothingWithoutOpenPositions(): void
    {
        $tickers = $this->createConfiguredStub(TickerRepository::class, ['findBy' => [$this->ticker()]]);

        $import = $this->service(tickers: $tickers, hasOpenPosition: false)
            ->importExport(['TEST' => ['price' => 11.0, 'result' => 10.0]]);

        self::assertNull($import['dataSet']);
        self::assertSame(['TEST'], $import['skippedSymbols']);
    }

    public function testATickerWithoutRegularDividendExpectsNothing(): void
    {
        $result = $this->service(hasRegularDividend: false)->recalculate($this->dataSet(), false);

        self::assertSame(0.0, $result['rows'][self::ISIN]['expectedDistribution']);
        self::assertSame(0.0, $result['totalExpectedDistribution']);
    }

    public function testAnUnavailableExchangeRateLeavesTheExpectedDistributionUnknown(): void
    {
        $rates = $this->createStub(DividendExchangeRateResolverInterface::class);
        $rates->method('getRateForCalendar')->willThrowException(new \RuntimeException('No rate'));

        $result = $this->service(rates: $rates)->recalculate($this->dataSet(), false);

        self::assertNull($result['rows'][self::ISIN]['expectedDistribution']);
        self::assertNull($result['totalExpectedDistribution']);
    }

    public function testSavingDoesNotNeedExchangeRates(): void
    {
        $rates = $this->createMock(DividendExchangeRateResolverInterface::class);
        $rates->expects(self::never())->method('getRateForCalendar');

        $this->service(rates: $rates)->recalculate($this->dataSet(), true);
    }

    public function testAZeroAllocationDoesNotCrash(): void
    {
        $dataSet = new IncomesSharesDataSet();
        $dataSet->getShares()->add($this->share(allocation: 0.0));

        $result = $this->service()->recalculate($dataSet, false);

        self::assertSame(0.0, $result['rows'][self::ISIN]['totalReturnPercentage']);
    }

    public function testTheStoredDataSetUsesItsStoredShareAmount(): void
    {
        $share = $this->share();
        $share->setPosition($this->createConfiguredStub(Position::class, ['getAmount' => 0.0]));
        $dataSet = new IncomesSharesDataSet();
        $dataSet->setUuid(Uuid::v7());

        $display = $this->service(storedShares: [$share])->rowsForDisplay($dataSet);

        self::assertSame(self::SHARES, $display['rows'][self::ISIN]['amount']);
        self::assertEqualsWithDelta(self::NET_EXPECTED, $display['totalExpectedDistribution'], 0.0001);
    }

    public function testTheTotalMatchesTheTableWhenATickerIsStoredTwice(): void
    {
        $dataSet = new IncomesSharesDataSet();
        $dataSet->setUuid(Uuid::v7());

        $display = $this->service(storedShares: [$this->share(), $this->share()])->rowsForDisplay($dataSet);

        self::assertCount(1, $display['rows']);
        self::assertEqualsWithDelta(self::NET_EXPECTED, $display['totalExpectedDistribution'], 0.0001);
    }

    public function testRecalculatingKeepsTheCreationDate(): void
    {
        $createdAt = new \DateTimeImmutable('2026-01-15');
        $dataSet = $this->dataSet();
        $dataSet->setCreatedAt($createdAt);

        $this->service()->recalculate($dataSet, true);

        self::assertSame($createdAt, $dataSet->getCreatedAt());
        self::assertNotNull($dataSet->getUpdatedAt());
    }

    public function testANewDataSetGetsACreationDate(): void
    {
        $dataSet = $this->dataSet();

        $this->service()->calculateFromPositions($dataSet, false);

        self::assertNotNull($dataSet->getCreatedAt());
    }

    public function testOnlyTickersWithAnOpenPositionAreAdded(): void
    {
        $tickers = $this->createConfiguredStub(TickerRepository::class, ['findBy' => [$this->ticker()]]);
        $dataSet = new IncomesSharesDataSet();

        $this->service(tickers: $tickers, hasOpenPosition: false)->addSharesForIsins($dataSet, [self::ISIN]);
        self::assertCount(0, $dataSet->getShares());

        $this->service(tickers: $tickers)->addSharesForIsins($dataSet, [self::ISIN]);
        self::assertCount(1, $dataSet->getShares());
    }

    /**
     * @param array<int, CorporateAction[]> $actions
     * @param IncomesSharesData[] $storedShares
     */
    private function service(
        ?DividendExchangeRateResolverInterface $rates = null,
        ?TickerRepository $tickers = null,
        array $actions = [],
        array $storedShares = [],
        bool $hasRegularDividend = true,
        bool $hasOpenPosition = true,
    ): IncomesSharesDataSetService {
        $position = $this->createConfiguredStub(Position::class, [
            'getAllocation' => 100.0,
            'getAmount' => self::SHARES,
            'getTicker' => $this->ticker(),
        ]);
        $calendar = $this->createConfiguredStub(Calendar::class, [
            'getCashAmount' => self::CASH_AMOUNT,
            'getCreatedAt' => new \DateTimeImmutable('-2 days'),
        ]);

        return new IncomesSharesDataSetService(
            $this->createConfiguredStub(PositionRepository::class, [
                'findOneBy' => $hasOpenPosition ? $position : null,
                'findBy' => $hasOpenPosition ? [$position] : [],
            ]),
            $this->createConfiguredStub(PaymentRepository::class, ['getSumDividendsByPosition' => 3.0]),
            $tickers ?? $this->createStub(TickerRepository::class),
            $this->createConfiguredStub(IncomesSharesDataRepository::class, ['findByDataset' => $storedShares]),
            $this->createConfiguredStub(DividendServiceInterface::class, [
                'getRegularCalendar' => $hasRegularDividend ? $calendar : null,
            ]),
            $rates ?? $this->createConfiguredStub(DividendExchangeRateResolverInterface::class, [
                'getRateForCalendar' => self::RATE,
            ]),
            $this->createConfiguredStub(DividendTaxRateResolverInterface::class, [
                'getTaxRateForCalendar' => self::TAX_RATE,
            ]),
            $this->createConfiguredStub(CorporateActionDataProvider::class, ['load' => $actions]),
            new DividendAdjuster(),
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(LoggerInterface::class)
        );
    }

    private function split(string $eventDate, float $ratio = 2.0): CorporateAction
    {
        return $this->createConfiguredStub(CorporateAction::class, [
            'getEventDate' => new \DateTime($eventDate),
            'getRatio' => $ratio,
        ]);
    }

    private function ticker(int $id = self::TICKER_ID, string $isin = self::ISIN): Ticker
    {
        return $this->createConfiguredStub(Ticker::class, [
            'getId' => $id,
            'getIsin' => $isin,
            'getSymbol' => 'TEST',
            'getFullname' => 'Test Income',
        ]);
    }

    private function dataSet(): IncomesSharesDataSet
    {
        $dataSet = new IncomesSharesDataSet();
        $dataSet->getShares()->add($this->share());

        return $dataSet;
    }

    private function share(float $allocation = 100.0, ?Ticker $ticker = null): IncomesSharesData
    {
        $share = new IncomesSharesData();
        $share->setTicker($ticker ?? $this->ticker());
        $share->setPrice(11.0);
        $share->setProfitLoss(10.0);
        $share->setAllocation($allocation);
        $share->setAmount(self::SHARES);
        $share->setDistributions(3.0);

        return $share;
    }
}
