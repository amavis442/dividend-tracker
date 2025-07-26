<?php

namespace App\Dto;

final class ExchangeTaxDto
{
    public function __construct(
        public readonly float $exchangeRate,
        public readonly float $taxAmount,
        public readonly string $currency,
        // Add any other contextual fields you need
    ) {}
}
