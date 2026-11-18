<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataProvider;

use App\Contracts\ExchangeRate\DividendExchangeRateResolverInterface;
use App\DataProvider\BasicDatasetDataProvider;
use App\DataProvider\CorporateActionDataProvider;
use App\DataProvider\DividendDataProvider;
use App\Decorator\Factory\AdjustedPositionDecoratorFactory;
use App\Entity\Calendar;
use App\Entity\Currency;
use App\Exception\ExchangeRateUnavailableException;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Repository\PositionRepository;
use App\Repository\TransactionRepository;
use App\Service\Dividend\DividendAdjuster;
use App\Service\Transaction\TransactionAdjuster;
use PHPUnit\Framework\TestCase;

final class BasicDatasetDataProviderTest extends TestCase
{
    private const TICKER_ID = 9;

    public function testTheRateFollowsTheCurrencyOfTheDividend(): void
    {
        $calendar = $this->createConfiguredStub(Calendar::class, [
            'getCurrency' => $this->createStub(Currency::class),
        ]);

        $rates = $this->createMock(DividendExchangeRateResolverInterface::class);
        $rates->expects(self::once())->method('getRateForCalendar')->with($calendar)->willReturn(0.8);
        $rates->expects(self::never())->method('getRateForTicker');

        $data = $this->provider($rates, [self::TICKER_ID => [1 => $calendar]])->getDataForYield();

        self::assertSame([self::TICKER_ID => 0.8], $data->exchangeRates);
    }

    public function testATickerWithoutDividendsUsesItsOwnCurrency(): void
    {
        $rates = $this->createMock(DividendExchangeRateResolverInterface::class);
        $rates->expects(self::never())->method('getRateForCalendar');
        $rates->expects(self::once())->method('getRateForTicker')->willReturn(0.5);

        $data = $this->provider($rates, [])->getDataForYield();

        self::assertSame([self::TICKER_ID => 0.5], $data->exchangeRates);
    }

    public function testAMissingRateWithoutDividendsDoesNotBreakTheDataset(): void
    {
        $rates = $this->createStub(DividendExchangeRateResolverInterface::class);
        $rates->method('getRateForTicker')->willThrowException(new ExchangeRateUnavailableException('No rate'));

        $data = $this->provider($rates, [])->getDataForYield();

        self::assertSame([self::TICKER_ID => 0.0], $data->exchangeRates);
    }

    public function testADividendWithoutCurrencyUsesTheTickerCurrency(): void
    {
        $calendar = $this->createConfiguredStub(Calendar::class, ['getCurrency' => null]);
        $rates = $this->createConfiguredStub(DividendExchangeRateResolverInterface::class, [
            'getRateForTicker' => 0.5,
        ]);

        $data = $this->provider($rates, [self::TICKER_ID => [1 => $calendar]])->getDataForYield();

        self::assertSame([self::TICKER_ID => 0.5], $data->exchangeRates);
    }

    /**
     * @param array<int, array<int, Calendar>> $calendars
     */
    private function provider(DividendExchangeRateResolverInterface $rates, array $calendars): BasicDatasetDataProvider
    {
        $ticker = $this->createConfiguredStub(Ticker::class, [
            'getId' => self::TICKER_ID,
            'getCurrency' => $this->createStub(Currency::class),
        ]);
        $position = $this->createConfiguredStub(Position::class, ['getId' => 5, 'getTicker' => $ticker]);

        return new BasicDatasetDataProvider(
            $this->createConfiguredStub(PositionRepository::class, ['getForYieldView' => [$position]]),
            $this->createConfiguredStub(TransactionRepository::class, ['findByPositionIds' => []]),
            $this->createConfiguredStub(CorporateActionDataProvider::class, ['load' => []]),
            $rates,
            $this->createStub(TransactionAdjuster::class),
            $this->createStub(DividendAdjuster::class),
            $this->createConfiguredStub(DividendDataProvider::class, ['load' => $calendars]),
            $this->createStub(AdjustedPositionDecoratorFactory::class)
        );
    }
}
