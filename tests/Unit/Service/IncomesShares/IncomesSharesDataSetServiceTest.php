<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\IncomesShares;

use App\Contracts\ExchangeRate\ExchangeRateInterface;
use App\Entity\Calendar;
use App\Entity\IncomesSharesData;
use App\Entity\IncomesSharesDataSet;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Repository\IncomesSharesDataRepository;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Service\IncomesShares\IncomesSharesDataSetService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class IncomesSharesDataSetServiceTest extends TestCase
{
    private const ISIN = 'IE00TEST0001';

    public function testRecalculateAddsTheExpectedDistribution(): void
    {
        $dataSet = $this->dataSet($this->ticker(cashAmount: 0.5));

        $result = $this->service()->recalculate($dataSet, false);

        self::assertEqualsWithDelta(2.5, $result['rows'][self::ISIN]['expectedDistribution'], 0.0001);
        self::assertEqualsWithDelta(2.5, $result['totalExpectedDistribution'], 0.0001);
    }

    public function testCalculateFromPositionsAddsTheExpectedDistribution(): void
    {
        $dataSet = $this->dataSet($this->ticker(cashAmount: 0.5));

        $result = $this->service()->calculateFromPositions($dataSet, false);

        self::assertEqualsWithDelta(2.5, $result['rows'][self::ISIN]['expectedDistribution'], 0.0001);
        self::assertEqualsWithDelta(2.5, $result['totalExpectedDistribution'], 0.0001);
    }

    public function testATickerWithoutCalendarExpectsNoDistribution(): void
    {
        $dataSet = $this->dataSet($this->ticker(cashAmount: null));

        $result = $this->service()->recalculate($dataSet, false);

        self::assertSame(0.0, $result['rows'][self::ISIN]['expectedDistribution']);
        self::assertSame(0.0, $result['totalExpectedDistribution']);
    }

    public function testTheTotalMatchesTheTableWhenATickerIsEnteredTwice(): void
    {
        $ticker = $this->ticker(cashAmount: 0.5);
        $dataSet = $this->dataSet($ticker);
        $dataSet->getShares()->add($this->share($ticker));

        $result = $this->service()->recalculate($dataSet, false);

        self::assertCount(1, $result['rows']);
        self::assertEqualsWithDelta(2.5, $result['totalExpectedDistribution'], 0.0001);
    }

    public function testTheDisplayedTotalMatchesTheTableWhenATickerIsStoredTwice(): void
    {
        $position = $this->createConfiguredStub(Position::class, ['getAmount' => 10.0]);
        $ticker = $this->ticker(cashAmount: 0.5);
        $shares = [$this->share($ticker), $this->share($ticker)];
        foreach ($shares as $share) {
            $share->setPosition($position);
        }
        $dataSet = new IncomesSharesDataSet();
        $dataSet->setUuid(Uuid::v7());

        $display = $this->service(storedShares: $shares)->rowsForDisplay($dataSet);

        self::assertCount(1, $display['rows']);
        self::assertEqualsWithDelta(2.5, $display['totalExpectedDistribution'], 0.0001);
    }

    public function testSavingDoesNotNeedExchangeRates(): void
    {
        $dataSet = $this->dataSet($this->ticker(cashAmount: 0.5));
        $exchangeRates = $this->createMock(ExchangeRateInterface::class);
        $exchangeRates->expects(self::never())->method('getRates');

        $this->service($exchangeRates)->recalculate($dataSet, true);
    }

    /**
     * @param IncomesSharesData[] $storedShares
     */
    private function service(
        ?ExchangeRateInterface $exchangeRates = null,
        array $storedShares = []
    ): IncomesSharesDataSetService {
        $position = $this->createConfiguredStub(Position::class, [
            'getAllocation' => 100.0,
            'getAmount' => 10.0,
        ]);

        return new IncomesSharesDataSetService(
            $this->createConfiguredStub(PositionRepository::class, ['findOneBy' => $position]),
            $this->createConfiguredStub(PaymentRepository::class, ['getSumDividendsByPosition' => 3.0]),
            $this->createStub(TickerRepository::class),
            $this->createConfiguredStub(IncomesSharesDataRepository::class, ['findByDataset' => $storedShares]),
            $exchangeRates ?? $this->createConfiguredStub(ExchangeRateInterface::class, [
                'getRates' => ['USD' => 2.0],
            ]),
            $this->createStub(EntityManagerInterface::class)
        );
    }

    private function ticker(?float $cashAmount): Ticker
    {
        $calendars = $cashAmount === null
            ? []
            : [$this->createConfiguredStub(Calendar::class, ['getCashAmount' => $cashAmount])];

        return $this->createConfiguredStub(Ticker::class, [
            'getId' => 9,
            'getIsin' => self::ISIN,
            'getFullname' => 'Test Income',
            'getCalendars' => new ArrayCollection($calendars),
        ]);
    }

    private function dataSet(Ticker $ticker): IncomesSharesDataSet
    {
        $dataSet = new IncomesSharesDataSet();
        $dataSet->getShares()->add($this->share($ticker));

        return $dataSet;
    }

    private function share(Ticker $ticker): IncomesSharesData
    {
        $share = new IncomesSharesData();
        $share->setTicker($ticker);
        $share->setPrice(11.0);
        $share->setProfitLoss(10.0);
        $share->setAllocation(100.0);
        $share->setAmount(10.0);
        $share->setDistributions(3.0);

        return $share;
    }
}
