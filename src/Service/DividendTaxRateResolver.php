<?php
namespace App\Service;

use App\Entity\Calendar;
use App\Entity\Constants;

class DividendTaxRateResolver implements DividendTaxRateResolverInterface {
    /**
     * Needs Calendar, Ticker, Tax Entity data
     * $calendar->getTicker()->getTax()
     */
    public function getTaxRateForCalendar(Calendar $calendar): float
    {
        $dividendTax = 0.15;
        $taxRate = 0;

        $ticker = $calendar->getTicker();
        $tax = $ticker->getTax();
        if ($tax) {
            $taxRate = $tax->getTaxRate();
            return $taxRate;
        }

        switch ($calendar->getCurrency()->getSymbol()) {
            case 'EUR':
                $dividendTax = Constants::TAX / 100;
                break;
            case 'USD':
                $dividendTax = Constants::TAX / 100;
                break;
            case 'GB':
                $dividendTax = Constants::TAX_GB / 100;
                break;
            case 'CAD':
                $dividendTax = Constants::TAX / 100;
                break;
            default:
                $dividendTax = Constants::TAX / 100;
                break;
        }

        return $dividendTax;
    }
}
