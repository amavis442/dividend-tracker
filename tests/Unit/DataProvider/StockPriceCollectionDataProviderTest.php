<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataProvider;

use App\DataProvider\StockPriceCollectionDataProvider;
use App\Entity\Ticker;
use App\Exception\ExchangeRateUnavailableException;
use App\Repository\TickerRepository;
use App\Service\StockPriceService;
use PHPUnit\Framework\TestCase;

final class StockPriceCollectionDataProviderTest extends TestCase
{
    public function testAMissingCurrencyRateOnlyAffectsThatPrice(): void
    {
        $prices = $this->createStub(StockPriceService::class);
        $prices->method('getMarketPrice')->willReturnCallback(
            static fn(string $symbol): float => $symbol === 'GOOD'
                ? 10.0
                : throw new ExchangeRateUnavailableException('No rate')
        );

        $collection = $this->provider($prices)->getCollection();

        self::assertNull($collection[0]->getPrice());
        self::assertSame(10.0, $collection[1]->getPrice());
    }

    public function testAProviderFailureIsReported(): void
    {
        $prices = $this->createStub(StockPriceService::class);
        $prices->method('getMarketPrice')->willThrowException(new ExchangeRateUnavailableException('Down', true));

        $this->expectException(ExchangeRateUnavailableException::class);

        $this->provider($prices)->getCollection();
    }

    private function provider(StockPriceService $prices): StockPriceCollectionDataProvider
    {
        $tickers = array_map(
            fn(string $symbol): Ticker => $this->createConfiguredStub(Ticker::class, ['getSymbol' => $symbol]),
            ['BAD', 'GOOD']
        );

        return new StockPriceCollectionDataProvider(
            $this->createConfiguredStub(TickerRepository::class, ['getActive' => $tickers]),
            $prices
        );
    }
}
