<?php

declare(strict_types=1);

namespace App\Service\ExchangeRate;

use App\Dto\ExchangeTaxDto;
use App\Entity\Ticker;
use App\Entity\Calendar;

interface ExchangeAndTaxResolverInterface
{
    public function resolve(Ticker $ticker, Calendar $calendar): ExchangeTaxDto;
}
