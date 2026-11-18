<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\Entity\Calendar;
use App\Helper\DividendCalendars;
use PHPUnit\Framework\TestCase;

final class DividendCalendarsTest extends TestCase
{
    public function testTheLastPaidDividendIsTheMostRecentPastPayment(): void
    {
        $old = $this->calendar('2026-01-15');
        $paid = $this->calendar('2026-03-15');
        $future = $this->calendar('2026-05-15');

        self::assertSame(
            $paid,
            DividendCalendars::lastPaid([$future, $old, $paid], new \DateTimeImmutable('2026-04-01'))
        );
    }

    public function testAPaymentToday(): void
    {
        $today = $this->calendar('2026-04-01');

        self::assertSame(
            $today,
            DividendCalendars::lastPaid([$today], new \DateTimeImmutable('2026-04-01 08:00:00'))
        );
    }

    public function testNothingIsPaidYet(): void
    {
        self::assertNull(
            DividendCalendars::lastPaid([$this->calendar('2026-05-15')], new \DateTimeImmutable('2026-04-01'))
        );
    }

    private function calendar(string $paymentDate): Calendar
    {
        return $this->createConfiguredStub(Calendar::class, ['getPaymentDate' => new \DateTime($paymentDate)]);
    }
}
