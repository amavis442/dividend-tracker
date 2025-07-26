<?php

namespace App\Service;

use App\Dto\ExchangeTaxDto;
use App\Entity\Ticker;
use App\Entity\Calendar;

interface ExchangeAndTaxResolverInterface
{
    public function resolve(Ticker $ticker, Calendar $calendar): ExchangeTaxDto;
}
