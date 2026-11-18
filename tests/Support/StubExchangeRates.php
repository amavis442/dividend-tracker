<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Contracts\ExchangeRate\ExchangeRateInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Replaces the exchange rate provider in functional tests.
 */
final class StubExchangeRates implements ExchangeRateInterface, ResetInterface
{
    /**
     * @param array<string, float> $rates
     */
    public function __construct(public array $rates = ['USD' => 2.0])
    {
    }

    public function getRates(): array
    {
        return $this->rates;
    }

    public function reset(): void
    {
    }
}
