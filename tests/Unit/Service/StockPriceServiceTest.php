<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Contracts\ExchangeRate\ExchangeRateInterface;
use App\Exception\ExchangeRateUnavailableException;
use App\Service\StockPriceService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class StockPriceServiceTest extends TestCase
{
    public function testAQuoteIsConvertedWithItsCurrencyRate(): void
    {
        self::assertEqualsWithDelta(8.0, $this->service(['USD' => 1.25])->getQuote('ABC'), 0.0001);
    }

    public function testAMissingRateIsReported(): void
    {
        $this->expectException(ExchangeRateUnavailableException::class);

        $this->service([])->getQuote('ABC');
    }

    public function testAZeroRateIsReported(): void
    {
        $this->expectException(ExchangeRateUnavailableException::class);

        $this->service(['USD' => 0.0])->getQuote('ABC');
    }

    /**
     * @param array<string, float> $rates
     */
    private function service(array $rates): StockPriceService
    {
        $service = new StockPriceService(
            $this->createConfiguredStub(ExchangeRateInterface::class, ['getRates' => $rates]),
            $this->createStub(CacheInterface::class),
            $this->createStub(HttpClientInterface::class)
        );
        (new \ReflectionProperty(StockPriceService::class, 'data'))->setValue($service, [
            'ABC' => ['regularMarketPrice' => 10.0, 'currency' => 'usd'],
        ]);

        return $service;
    }
}
