<?php

namespace App\Helper;

use DateTime;

class DateHelper
{
    public function getInterval(string $interval): array
    {
        $currentDate = new DateTime();
        $startDate = new DateTime();
        $endDate = new DateTime();
        switch ($interval) {
            case 'Year': // year
                $startDate = new DateTime($currentDate->format('Y') . '-01-01');
                break;
            case 'Month': // month
                $startDate->modify('last day of previous month');
                $startDate->modify('+1 day');
                break;
            case 'Week': // week
                $startDate->modify('monday this week');
                break;
            case 'Quator': // quaterly
                $startDate = $this->lastQuater($currentDate);
                break;
        }

        return [$startDate, $endDate];
    }

    public function lastQuater(DateTime $currentDate): DateTime
    {
        $currentMonth = $currentDate->format('m');
        if ($currentMonth < 1 || $currentMonth > 12) {
            throw new \OutOfBoundsException("current month is 1 < and > 12");
        }

        if ($currentMonth >= 1 && $currentMonth <= 3) {
            $startDate = new DateTime('first day of january');
        }
        if ($currentMonth >= 4 && $currentMonth <= 6) {
            $startDate = new DateTime('first day of april');
        }
        if ($currentMonth >= 7 && $currentMonth <= 9) {
            $startDate = new DateTime('first day of july');
        }
        if ($currentMonth >= 10 && $currentMonth <= 12) {
            $startDate = new DateTime('first day of october');
        }
        return $startDate ?? null;
    }

    public function quaterToDates(int $quator, int $year): array
    {
        if ($quator < 1 || $quator > 4) {
            throw new \OutOfBoundsException("Quator is out of bound. Make sure it is between 1 and 4");
        }

        if ($quator === 1) {
            $startDate = new DateTime('first day of january ' . $year);
            $endDate = new DateTime('last day of march ' . $year);
        }
        if ($quator === 2) {
            $startDate = new DateTime('first day of april ' . $year);
            $endDate = new DateTime('last day of june ' . $year);
        }
        if ($quator === 3) {
            $startDate = new DateTime('first day of july ' . $year);
            $endDate = new DateTime('last day of september ' . $year);
        }
        if ($quator === 4) {
            $startDate = new DateTime('first day of october ' . $year);
            $endDate = new DateTime('last day of december ' . $year);
        }
        if (!isset($startDate) || !isset($endDate)) {
            throw new \RuntimeException("Can't get the dates for the quator. Make sure quator is between 1 and 4");
        }

        return [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')];
    }

    public function monthToDates(int $month, int $year): array
    {
        if ($month === 1) {
            $startDate = new DateTime('first day of january ' . $year);
            $endDate = new DateTime('last day of january ' . $year);
        }
        if ($month === 2) {
            $startDate = new DateTime('first day of february ' . $year);
            $endDate = new DateTime('last day of february ' . $year);
        }
        if ($month === 3) {
            $startDate = new DateTime('first day of march ' . $year);
            $endDate = new DateTime('last day of march ' . $year);
        }
        if ($month === 4) {
            $startDate = new DateTime('first day of april ' . $year);
            $endDate = new DateTime('last day of april ' . $year);
        }
        if ($month === 5) {
            $startDate = new DateTime('first day of may ' . $year);
            $endDate = new DateTime('last day of may ' . $year);
        }
        if ($month === 6) {
            $startDate = new DateTime('first day of june ' . $year);
            $endDate = new DateTime('last day of june ' . $year);
        }
        if ($month === 7) {
            $startDate = new DateTime('first day of july ' . $year);
            $endDate = new DateTime('last day of july ' . $year);
        }
        if ($month === 8) {
            $startDate = new DateTime('first day of august ' . $year);
            $endDate = new DateTime('last day of august ' . $year);
        }
        if ($month === 9) {
            $startDate = new DateTime('first day of september ' . $year);
            $endDate = new DateTime('last day of september ' . $year);
        }
        if ($month === 10) {
            $startDate = new DateTime('first day of october ' . $year);
            $endDate = new DateTime('last day of october ' . $year);
        }
        if ($month === 11) {
            $startDate = new DateTime('first day of november ' . $year);
            $endDate = new DateTime('last day of november ' . $year);
        }
        if ($month === 12) {
            $startDate = new DateTime('first day of december ' . $year);
            $endDate = new DateTime('last day of december ' . $year);
        }
        if (!isset($startDate) || !isset($endDate)) {
            throw new \RuntimeException("Can't get the dates for the months. Make sure month is between 1 and 12");
        }

        return [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')];
    }
}
