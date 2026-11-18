<?php

namespace App\Service\Dividend;

use App\Entity\Calendar;
use App\Helper\CorporateActionRatio;

class DividendAdjuster
{
    /**
     * Expresses a dividend per share after later reverse splits. The moment of
     * recording decides: dividends imported after a split, also for earlier
     * dates, already arrive split-adjusted from the data providers.
     */
    public function getAdjustedDividend(Calendar $calendar, array $actions): float
    {
        $cashAmount = $calendar->getCashAmount();
        $createdAt = $calendar->getCreatedAt();

        foreach ($actions as $action) {
            if (CorporateActionRatio::appliesTo($action, $createdAt)) {
                $cashAmount /= CorporateActionRatio::of($action); // Applies reverse split
            }
        }

        $calendar->setAdjustedCashAmount($cashAmount);

        return $cashAmount;
    }
}
