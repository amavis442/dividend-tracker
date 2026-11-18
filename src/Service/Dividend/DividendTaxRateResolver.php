<?php

declare(strict_types=1);

namespace App\Service\Dividend;

use App\Contracts\Dividend\DividendTaxRateResolverInterface;
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
        $ticker = $calendar->getTicker();
        $tax = $ticker->getTax();
        if ($tax) {
            return $tax->getTaxRate();
        }

        return match (($calendar->getCurrency() ?? $ticker->getCurrency())?->getSymbol()) {
            'GB', 'GBX', 'GBP' => Constants::TAX_GB_RATE,
            default => Constants::DEFAULT_TAX_RATE,
        };
    }
}
