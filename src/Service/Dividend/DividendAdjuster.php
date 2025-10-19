<?php

namespace App\Service\Dividend;

use App\Entity\Calendar;

class DividendAdjuster
{

    public function getAdjustedDividend(Calendar $calendar, array $actions): float
    {
        $cashAmount = $calendar->getCashAmount();

        foreach ($actions as $action) {
            if ($calendar->getCreatedAt() < $action->getEventDate()) {
                $cashAmount /= $action->getRatio(); // Applies reverse split
            }
        }

        $calendar->setAdjustedCashAmount($cashAmount);

        return $cashAmount;
    }
}
