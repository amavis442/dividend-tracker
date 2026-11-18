<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\ExchangeRate;

use App\Contracts\ExchangeRate\ExchangeRateInterface;
use App\Service\ExchangeRate\WisselkoersNlExchangeRateService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class WisselkoersNlExchangeRateServiceTest extends TestCase
{
    private const BODY = '[{"Shortname":"usd","Last":"1,25"},{"Shortname":"gbp","Last":"0,8"}]';

    public function testRatesIncludeThePoundVariants(): void
    {
        $rates = $this->service(new MockHttpClient([new MockResponse(self::BODY)]))->getRates();

        self::assertSame(1.25, $rates['USD']);
        self::assertSame(0.8, $rates['GB']);
        self::assertEqualsWithDelta(80.0, $rates['GBX'], 0.0001);
    }

    public function testRatesAreFetchedOncePerRequest(): void
    {
        $client = new MockHttpClient([new MockResponse(self::BODY)]);
        $service = $this->service($client, new BrokenCachePool());

        $service->getRates();
        $service->getRates();

        self::assertSame(1, $client->getRequestsCount());
    }

    public function testAFailedRequestGivesNoRatesAndIsNotCached(): void
    {
        $cache = new ArrayAdapter();
        $client = new MockHttpClient([new MockResponse('', ['http_code' => 500])]);

        self::assertSame([], $this->service($client, $cache)->getRates());
        self::assertFalse($cache->hasItem(ExchangeRateInterface::CACHE_KEY));
    }

    public function testAFailedRequestIsRetried(): void
    {
        $client = new MockHttpClient([
            new MockResponse('', ['http_code' => 500]),
            new MockResponse(self::BODY),
        ]);
        $service = $this->service($client, new BrokenCachePool());

        self::assertSame([], $service->getRates());
        self::assertSame(1.25, $service->getRates()['USD']);
    }

    private function service(
        MockHttpClient $client,
        ?\Psr\Cache\CacheItemPoolInterface $cache = null
    ): WisselkoersNlExchangeRateService {
        return new WisselkoersNlExchangeRateService($client, $cache ?? new ArrayAdapter());
    }
}

/**
 * A cache that stores nothing, like the application when Redis is down.
 */
final class BrokenCachePool extends ArrayAdapter
{
    public function hasItem(mixed $key): bool
    {
        return false;
    }
}
