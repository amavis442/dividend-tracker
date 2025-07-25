<?php
namespace App\Service;

use App\Entity\Calendar;

interface DividendExchangeRateResolverInterface
{
    public function getRateForCalendar(Calendar $calendar): float;
}
