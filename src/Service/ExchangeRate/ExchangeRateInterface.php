<?php

declare(strict_types=1);

namespace App\Service\ExchangeRate;

interface ExchangeRateInterface
{
    public const CACHE_KEY = 'exchangerates';
    public function getRates(): array;
}
