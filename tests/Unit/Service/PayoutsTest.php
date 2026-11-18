<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Contracts\Dividend\DividendServiceInterface;
use App\DataProvider\CorporateActionDataProvider;
use App\DataProvider\DividendDataProvider;
use App\DataProvider\TransactionDataProvider;
use App\Entity\Calendar;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Repository\DividendCalendarRepository;
use App\Repository\DividendTrackerRepository;
use App\Repository\PaymentRepository;
use App\Service\Payouts;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class PayoutsTest extends TestCase
{
    public function testPaymentsWithoutAnyDataDoesNotCrash(): void
    {
        $result = $this->payments(payments: [], allocations: [], calendars: []);

        self::assertSame([date('Y-m')], $result['labels']);
        self::assertSame([], $result['dividends']);
        self::assertSame([], $result['trendline']);
        self::assertSame([(int) date('Ym') => 0.0], $result['yields']);
        self::assertSame([0.0], $result['trendlineYield']);
        self::assertSame([null], $result['forecast']);
        self::assertSame([], $result['trendlineForeCastYield']);
    }

    public function testTrendlineOfASingleDataPointIsThatDataPoint(): void
    {
        $now = new DateTime('first day of this month');

        $result = $this->payments(
            payments: [$this->payment($now, 10.0)],
            allocations: [],
            calendars: []
        );

        self::assertSame([10.0], $result['trendline']);
    }

    public function testTrendlineFollowsTheDataPoints(): void
    {
        $result = $this->payments(
            payments: [
                $this->payment(new DateTime('first day of -2 months'), 10.0),
                $this->payment(new DateTime('first day of -1 month'), 20.0),
                $this->payment(new DateTime('first day of this month'), 30.0),
            ],
            allocations: [],
            calendars: []
        );

        self::assertEqualsWithDelta([10.0, 20.0, 30.0], $result['trendline'], 0.0001);
    }

    public function testAZeroAllocationDoesNotCrash(): void
    {
        $now = new DateTime('first day of this month');

        $result = $this->payments(
            payments: [$this->payment($now, 10.0)],
            allocations: [$this->allocation($now, 0.0)],
            calendars: []
        );

        self::assertSame([], $result['yields']);
    }

    public function testForecastIsLoadedAndAlignedWithTheChartLabels(): void
    {
        $lastMonth = new DateTime('first day of -1 month');
        $thisMonth = new DateTime('first day of this month');

        $position = $this->createConfiguredStub(Position::class, ['getId' => 5]);
        $ticker = $this->createConfiguredStub(Ticker::class, [
            'getId' => 9,
            'getPositions' => new ArrayCollection([$position]),
        ]);
        $calendar = $this->createConfiguredStub(Calendar::class, [
            'getTicker' => $ticker,
            'getPaymentDate' => $thisMonth,
        ]);

        $transactions = [5 => ['transaction']];
        $actions = [9 => ['action']];
        $dividends = [9 => [1 => $calendar]];

        $transactionProvider = $this->createMock(TransactionDataProvider::class);
        $transactionProvider->expects(self::once())->method('load')
            ->with([$position])->willReturn($transactions);
        $actionProvider = $this->createMock(CorporateActionDataProvider::class);
        $actionProvider->expects(self::once())->method('load')
            ->with([$ticker])->willReturn($actions);
        $dividendProvider = $this->createMock(DividendDataProvider::class);
        $dividendProvider->expects(self::once())->method('load')
            ->with([$ticker], null, null, [Calendar::REGULAR, Calendar::SUPPLEMENT, Calendar::SPECIAL])
            ->willReturn($dividends);

        $dividendService = $this->createMock(DividendServiceInterface::class);
        $dividendService->expects(self::once())->method('load')
            ->with($transactions, $actions, $dividends);
        $dividendService->method('getPositionAmount')->willReturn(2.0);
        $dividendService->method('getTotalNetDividend')->willReturn(5.0);

        $result = $this->payments(
            payments: [$this->payment($lastMonth, 10.0), $this->payment($thisMonth, 20.0)],
            allocations: [$this->allocation($thisMonth, 1200.0)],
            calendars: [$calendar],
            payouts: new Payouts($transactionProvider, $actionProvider, $dividendProvider),
            dividendService: $dividendService
        );

        self::assertSame([$lastMonth->format('Y-m'), $thisMonth->format('Y-m')], $result['labels']);
        self::assertSame([null, 5.0], $result['forecast']);
        self::assertEqualsWithDelta([(int) $thisMonth->format('Ym') => 5.0], $result['yields'], 0.0001);
    }

    public function testCurrentMonthWithoutPaymentsGetsOneYieldEntry(): void
    {
        $lastMonth = new DateTime('first day of -1 month');

        $result = $this->payments(
            payments: [$this->payment($lastMonth, 10.0)],
            allocations: [$this->allocation($lastMonth, 1200.0)],
            calendars: []
        );

        self::assertSame(
            [(int) $lastMonth->format('Ym'), (int) date('Ym')],
            array_keys($result['yields'])
        );
    }

    /**
     * @param array<int, array<string, mixed>> $payments
     * @param array<int, array<string, mixed>> $allocations
     * @param Calendar[] $calendars
     *
     * @return array<string, mixed>
     */
    private function payments(
        array $payments,
        array $allocations,
        array $calendars,
        ?Payouts $payouts = null,
        ?DividendServiceInterface $dividendService = null
    ): array {
        $payouts ??= new Payouts(
            $this->createStub(TransactionDataProvider::class),
            $this->createStub(CorporateActionDataProvider::class),
            $this->createStub(DividendDataProvider::class)
        );

        return $payouts->payments(
            $this->createConfiguredStub(PaymentRepository::class, ['getSumPayments' => $payments]),
            $this->createConfiguredStub(DividendTrackerRepository::class, ['getAllocationsPerMonth' => $allocations]),
            $dividendService ?? $this->createStub(DividendServiceInterface::class),
            $this->createConfiguredStub(DividendCalendarRepository::class, ['findForForecast' => $calendars])
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function payment(DateTime $month, float $dividend): array
    {
        return [
            'periodYear' => (int) $month->format('Y'),
            'periodMonth' => (int) $month->format('n'),
            'dividend' => $dividend,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function allocation(DateTime $month, float $allocation): array
    {
        return [
            'periodYear' => (int) $month->format('Y'),
            'periodMonth' => (int) $month->format('n'),
            'allocation' => $allocation,
        ];
    }
}
