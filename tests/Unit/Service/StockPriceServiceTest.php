<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Contracts\ExchangeRate\DividendExchangeRateResolverInterface;
use App\Exception\ExchangeRateUnavailableException;
use App\Service\StockPriceService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class StockPriceServiceTest extends TestCase
{
    public function testAQuoteIsConvertedWithItsCurrencyRate(): void
    {
        $rates = $this->createMock(DividendExchangeRateResolverInterface::class);
        $rates->expects(self::once())->method('getRateForCurrency')->with('USD')->willReturn(0.8);

        self::assertEqualsWithDelta(8.0, $this->service($rates)->getQuote('ABC'), 0.0001);
    }

    public function testAMissingRateIsReported(): void
    {
        $rates = $this->createStub(DividendExchangeRateResolverInterface::class);
        $rates->method('getRateForCurrency')->willThrowException(new ExchangeRateUnavailableException('No rate'));

        $this->expectException(ExchangeRateUnavailableException::class);

        $this->service($rates)->getQuote('ABC');
    }

    private function service(DividendExchangeRateResolverInterface $rates): StockPriceService
    {
        $service = new StockPriceService(
            $rates,
            $this->createStub(CacheInterface::class),
            $this->createStub(HttpClientInterface::class)
        );
        (new \ReflectionProperty(StockPriceService::class, 'data'))->setValue($service, [
            'ABC' => ['regularMarketPrice' => 10.0, 'currency' => 'usd'],
        ]);

        return $service;
    }
}
