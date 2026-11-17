<?php

declare(strict_types=1);

namespace App\Service\Dividend;

use App\Entity\Calendar;

interface DividendTaxRateResolverInterface
{
    /**
     * Needs Calendar, Ticker, Tax Entity data
     * $calendar->getTicker()->getTax()
     */
    public function getTaxRateForCalendar(Calendar $calendar): float;
}
