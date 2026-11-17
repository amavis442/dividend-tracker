<?php

declare(strict_types=1);

namespace App\Contracts\ExchangeRate;

use App\Entity\Calendar;
use App\Entity\Ticker;

interface DividendExchangeRateResolverInterface
{
    public function getRateForCalendar(Calendar $calendar): float;
    public function getRateForTicker(Ticker $ticker): float;
}
