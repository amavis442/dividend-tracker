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

    public function lastQuater(DateTime $currentDate)
    {
        $currentMonth = $currentDate->format('m');

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
        
        return $startDate;
    }
}
