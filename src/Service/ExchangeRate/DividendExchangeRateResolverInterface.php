<?php
namespace App\Service\ExchangeRate;

use App\Entity\Calendar;

interface DividendExchangeRateResolverInterface
{
    public function getRateForCalendar(Calendar $calendar): float;
}
