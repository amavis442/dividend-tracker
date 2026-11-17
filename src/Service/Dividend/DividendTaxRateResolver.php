<?php

namespace App\Service\Dividend;

use App\Entity\Calendar;
use App\Util\Constants;

class DividendTaxRateResolver implements DividendTaxRateResolverInterface
{
    /**
     * Needs Calendar, Ticker, Tax Entity data
     * $calendar->getTicker()->getTax()
     */
    public function getTaxRateForCalendar(Calendar $calendar): float
    {
        $tax = $calendar->getTicker()->getTax();
        if ($tax) {
            return $tax->getTaxRate();
        }

        switch ($calendar->getCurrency()->getSymbol()) {
            case 'EUR':
                $dividendTax = Constants::DEFAULT_TAX_RATE;
                break;
            case 'USD':
                $dividendTax = Constants::DEFAULT_TAX_RATE;
                break;
            case 'GB':
                $dividendTax = Constants::TAX_GB / 100;
                break;
            case 'CAD':
                $dividendTax = Constants::DEFAULT_TAX_RATE;
                break;
            default:
                $dividendTax = Constants::DEFAULT_TAX_RATE;
                break;
        }

        return $dividendTax;
    }
}
