<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Calendar;

/**
 * Selections on a list of dividend calendars.
 */
final class DividendCalendars
{
    /**
     * The most recent dividend with a payment date up to and including today.
     *
     * @param iterable<Calendar> $calendars
     */
    public static function lastPaid(iterable $calendars, ?\DateTimeInterface $today = null): ?Calendar
    {
        $lastPaidDay = ($today ?? new \DateTimeImmutable())->format('Y-m-d');

        $lastPaid = null;
        $latestDay = '';
        foreach ($calendars as $calendar) {
            $day = $calendar->getPaymentDate()->format('Y-m-d');
            if ($day <= $lastPaidDay && $day >= $latestDay) {
                $lastPaid = $calendar;
                $latestDay = $day;
            }
        }

        return $lastPaid;
    }
}
