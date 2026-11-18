<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\ExchangeRate;

use App\Contracts\ExchangeRate\ExchangeRateInterface;
use App\Entity\Calendar;
use App\Entity\Currency;
use App\Entity\Ticker;
use App\Exception\ExchangeRateUnavailableException;
use App\Service\ExchangeRate\DividendExchangeRateResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DividendExchangeRateResolverTest extends TestCase
{
    private const RATES = [
        'USD' => 1.25,
        'GBP' => 0.8,
        'GBX' => 80.0,
        'CAD' => 1.6,
        'CHF' => 0.9,
        'BRL' => 6.4,
    ];

    /**
     * @return iterable<string, array{string, float}>
     */
    public static function currencies(): iterable
    {
        yield 'euro' => ['EUR', 1.0];
        yield 'dollar' => ['USD', 0.8];
        yield 'pound' => ['GBP', 1.25];
        yield 'pound stored as GB' => ['GB', 1.25];
        yield 'pence' => ['GBX', 0.0125];
        yield 'canadian dollar' => ['CAD', 0.625];
        yield 'swiss franc' => ['CHF', 1 / 0.9];
        yield 'brazilian real' => ['BRL', 0.15625];
    }

    #[DataProvider('currencies')]
    public function testEveryCurrencyUsesItsOwnRate(string $symbol, float $expected): void
    {
        $resolver = $this->resolver(self::RATES);

        self::assertEqualsWithDelta($expected, $resolver->getRateForCalendar($this->calendar($symbol)), 0.00001);
        self::assertEqualsWithDelta($expected, $resolver->getRateForTicker($this->ticker($symbol)), 0.00001);
    }

    public function testADividendWithoutCurrencyUsesTheTickerCurrency(): void
    {
        $calendar = $this->createConfiguredStub(Calendar::class, [
            'getCurrency' => null,
            'getTicker' => $this->ticker('GBP'),
        ]);

        self::assertEqualsWithDelta(1.25, $this->resolver(self::RATES)->getRateForCalendar($calendar), 0.00001);
    }

    public function testADividendWithoutAnyCurrencyCountsAsDollars(): void
    {
        $calendar = $this->createConfiguredStub(Calendar::class, [
            'getCurrency' => null,
            'getTicker' => $this->createConfiguredStub(Ticker::class, ['getCurrency' => null]),
        ]);

        self::assertEqualsWithDelta(0.8, $this->resolver(self::RATES)->getRateForCalendar($calendar), 0.00001);
    }

    public function testARateCanBeRequestedByCurrency(): void
    {
        self::assertEqualsWithDelta(1.25, $this->resolver(self::RATES)->getRateForCurrency('GB'), 0.00001);
    }

    public function testEuroNeedsNoRates(): void
    {
        $rates = $this->createMock(ExchangeRateInterface::class);
        $rates->expects(self::never())->method('getRates');
        $resolver = new DividendExchangeRateResolver($rates, $this->createStub(TranslatorInterface::class));

        self::assertSame(1.0, $resolver->getRateForCalendar($this->calendar('EUR')));
    }

    public function testAMissingRateIsReported(): void
    {
        $this->expectException(ExchangeRateUnavailableException::class);

        $this->resolver(['USD' => 1.25])->getRateForCalendar($this->calendar('SEK'));
    }

    public function testAProviderFailureIsReported(): void
    {
        $rates = $this->createStub(ExchangeRateInterface::class);
        $rates->method('getRates')->willThrowException(new \RuntimeException('Unreadable'));
        $resolver = new DividendExchangeRateResolver($rates, $this->createStub(TranslatorInterface::class));

        try {
            $resolver->getRateForCalendar($this->calendar('USD'));
            self::fail('Expected the rate to be unavailable.');
        } catch (ExchangeRateUnavailableException $exception) {
            self::assertTrue($exception->providerFailed);
            self::assertNotNull($exception->getPrevious());
        }
    }

    public function testNoRatesAtAllCountsAsAProviderFailure(): void
    {
        try {
            $this->resolver([])->getRateForCalendar($this->calendar('USD'));
            self::fail('Expected the rate to be unavailable.');
        } catch (ExchangeRateUnavailableException $exception) {
            self::assertTrue($exception->providerFailed);
        }
    }

    public function testAZeroRateIsReported(): void
    {
        $this->expectException(ExchangeRateUnavailableException::class);

        $this->resolver(['USD' => 0.0])->getRateForTicker($this->ticker('USD'));
    }

    /**
     * @param array<string, float> $rates
     */
    private function resolver(array $rates): DividendExchangeRateResolver
    {
        return new DividendExchangeRateResolver(
            $this->createConfiguredStub(ExchangeRateInterface::class, ['getRates' => $rates]),
            $this->createConfiguredStub(TranslatorInterface::class, ['trans' => 'Unavailable'])
        );
    }

    private function calendar(string $symbol): Calendar
    {
        return $this->createConfiguredStub(Calendar::class, [
            'getCurrency' => $this->currency($symbol),
            'getTicker' => $this->ticker($symbol),
        ]);
    }

    private function ticker(string $symbol): Ticker
    {
        return $this->createConfiguredStub(Ticker::class, [
            'getSymbol' => 'TEST',
            'getCurrency' => $this->currency($symbol),
        ]);
    }

    private function currency(string $symbol): Currency
    {
        return $this->createConfiguredStub(Currency::class, ['getSymbol' => $symbol]);
    }
}
