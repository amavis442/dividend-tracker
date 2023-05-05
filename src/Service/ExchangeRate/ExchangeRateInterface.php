<?php

namespace App\Service\ExchangeRate;

interface ExchangeRateInterface
{
    public function getRates(): array;
}