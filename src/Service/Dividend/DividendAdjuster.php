<?php

namespace App\Service\Dividend;

use App\Entity\Calendar;
use App\Helper\CorporateActionRatio;

class DividendAdjuster
{
    /**
     * Expresses a dividend per share after later reverse splits. A dividend
     * needs adjusting when it was recorded or went ex-dividend before the split.
     */
    public function getAdjustedDividend(Calendar $calendar, array $actions): float
    {
        $cashAmount = $calendar->getCashAmount();
        $createdAt = $calendar->getCreatedAt();
        $exDividendDate = $calendar->getExDividendDate();

        foreach ($actions as $action) {
            if (
                CorporateActionRatio::appliesTo($action, $createdAt)
                || ($exDividendDate !== null && CorporateActionRatio::appliesTo($action, $exDividendDate))
            ) {
                $cashAmount /= CorporateActionRatio::of($action); // Applies reverse split
            }
        }

        $calendar->setAdjustedCashAmount($cashAmount);

        return $cashAmount;
    }
}
