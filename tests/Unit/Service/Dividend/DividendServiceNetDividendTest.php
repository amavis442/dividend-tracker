<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Dividend;

use App\Contracts\Decorator\AdjustedDividendDecoratorInterface;
use App\Contracts\Dividend\DividendTaxRateResolverInterface;
use App\Contracts\ExchangeRate\DividendExchangeRateResolverInterface;
use App\Contracts\ExchangeRate\ExchangeAndTaxResolverInterface;
use App\Contracts\Transaction\ShareEligibilityCalculatorInterface;
use App\Decorator\Factory\AdjustedDividendDecoratorFactory;
use App\Decorator\Factory\AdjustedPositionDecoratorFactory;
use App\Entity\Calendar;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Repository\DividendCalendarRepository;
use App\Service\Dividend\DividendService;
use PHPUnit\Framework\TestCase;

final class DividendServiceNetDividendTest extends TestCase
{
    private const POSITION_ID = 5;
    private const TICKER_ID = 9;
    private const CALENDAR_ID = 100;

    public function testNetDividendFindsDividendsByTickerId(): void
    {
        $service = $this->service([self::CALENDAR_ID => ['adjusted' => 2.0]]);
        [$position, $calendar] = $this->positionAndCalendar();
        $service->load([], [], [self::TICKER_ID => [self::CALENDAR_ID => $calendar]]);

        self::assertEqualsWithDelta(1.5, $service->getNetDividend($position, $calendar), 0.0001);
    }

    public function testNetDividendIsZeroWithoutDividendsForTheTicker(): void
    {
        $service = $this->service([self::CALENDAR_ID => ['adjusted' => 2.0]]);
        [$position, $calendar] = $this->positionAndCalendar();
        $service->load([], [], [self::POSITION_ID => [self::CALENDAR_ID => $calendar]]);

        self::assertSame(0.0, $service->getNetDividend($position, $calendar));
    }

    public function testNetDividendIsZeroWhenTheCalendarWasNotLoaded(): void
    {
        $service = $this->service([]);
        [$position, $calendar] = $this->positionAndCalendar();
        $service->load([], [], [self::TICKER_ID => []]);

        self::assertSame(0.0, $service->getNetDividend($position, $calendar));
    }

    /**
     * @param array<int, array<string, float>> $adjustedDividends
     */
    private function service(array $adjustedDividends): DividendService
    {
        $decorator = $this->createConfiguredStub(AdjustedDividendDecoratorInterface::class, [
            'getAdjustedDividend' => $adjustedDividends,
        ]);
        $dividendFactory = $this->createStub(AdjustedDividendDecoratorFactory::class);
        $dividendFactory->method('decorate')->willReturn($decorator);

        return new DividendService(
            $this->createConfiguredStub(DividendExchangeRateResolverInterface::class, ['getRateForCalendar' => 1.0]),
            $this->createConfiguredStub(DividendTaxRateResolverInterface::class, ['getTaxRateForCalendar' => 0.25]),
            $this->createStub(ShareEligibilityCalculatorInterface::class),
            $this->createStub(ExchangeAndTaxResolverInterface::class),
            $this->createStub(AdjustedPositionDecoratorFactory::class),
            $dividendFactory,
            $this->createStub(DividendCalendarRepository::class)
        );
    }

    /**
     * @return array{0: Position, 1: Calendar}
     */
    private function positionAndCalendar(): array
    {
        $ticker = $this->createConfiguredStub(Ticker::class, ['getId' => self::TICKER_ID]);
        $position = $this->createConfiguredStub(Position::class, [
            'getId' => self::POSITION_ID,
            'getTicker' => $ticker,
        ]);
        $calendar = $this->createConfiguredStub(Calendar::class, [
            'getId' => self::CALENDAR_ID,
            'getTicker' => $ticker,
            'getCashAmount' => 2.0,
        ]);

        return [$position, $calendar];
    }
}
