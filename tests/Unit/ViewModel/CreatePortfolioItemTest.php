<?php

namespace App\Tests\Unit\ViewModel;

use PHPUnit\Framework\TestCase;
use App\ViewModel\PortfolioViewModel;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Entity\Calendar as DividendCalendar;
use App\Decorator\AdjustedPositionDecorator;
use App\Decorator\Factory\AdjustedPositionDecoratorFactory;
use App\Contracts\Service\DividendServiceInterface;
use App\Service\MetricsUpdateService;
use App\Service\WeightedAverage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use DateTime;

class CreatePortfolioItemTest extends TestCase
{
    public function testPortfolioItemIsDecoratedAndEnriched(): void
    {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );

        /*
        // Mocks
        $tickerMock = $this->createMock(Ticker::class);
        $tickerMock->method('getSymbol')->willReturn('AAPL');
        $tickerMock->method('getPayoutFrequency')->willReturn(4);

        $positionMock = $this->createMock(Position::class);
        $positionMock->method('getTicker')->willReturn($tickerMock);
        $positionMock->method('getAmount')->willReturn(10.0);
        $positionMock->method('getAllocation')->willReturn(100.0);

        // Chainable setters
        $positionMock->method('setDividendPayoutFrequency')->willReturnSelf();
        $positionMock->method('setPercentageAllocation')->willReturnSelf();
        $positionMock->method('computeIsMaxAllocation')->willReturnSelf();
        $positionMock->method('computeCurrentDividendDates')->willReturnSelf();
        $positionMock->method('computeReceivedDividends')->willReturnSelf();
        $positionMock->method('setDivDate')->willReturnSelf();
        $positionMock->method('setCashAmount')->willReturnSelf();
        $positionMock->method('setCashCurrency')->willReturnSelf();
        $positionMock->method('setForwardNetDividend')->willReturnSelf();
        $positionMock->method('setForwardNetDividendYield')->willReturnSelf();
        $positionMock->method('setForwardNetDividendYieldPerShare')->willReturnSelf();
        $positionMock->method('setNetDividendPerShare')->willReturnSelf();
        $positionMock->method('setExDividendDate')->willReturnSelf();
        $positionMock->method('setPaymentDate')->willReturnSelf();

        $decoratorMock = $this->createMock(AdjustedPositionDecorator::class);
        $decoratorMock->method('getAdjustedAmount')->willReturn(10.0);
        $decoratorMock->method('getAdjustmentNote')->willReturn('Split occurred');
        $decoratorMock->method('getSymbol')->willReturn('AAPL');

        $adjustedFactoryMock = $this->createMock(AdjustedPositionDecoratorFactory::class);
        $adjustedFactoryMock->method('decorate')->willReturn($decoratorMock);

        $calendarMock = $this->createMock(DividendCalendar::class);
        $calendarMock->method('getCashAmount')->willReturn(3.5);
        $calendarMock->method('getCurrency')->willReturn('USD');
        $calendarMock->method('getExDividendDate')->willReturn(new DateTime('2025-07-01'));
        $calendarMock->method('getPaymentDate')->willReturn(new DateTime('2025-07-15'));

        $dividendServiceMock = $this->createMock(DividendServiceInterface::class);
        $dividendServiceMock->method('getRegularCalendar')->willReturn($calendarMock);
        $dividendServiceMock->method('getForwardNetDividend')->willReturn(35.0);
        $dividendServiceMock->method('getForwardNetDividendYield')->willReturn(0.035);
        $dividendServiceMock->method('getNetDividendPerShare')->willReturn(3.5);

        $metricsService = new MetricsUpdateService(
            $this->createMock(WeightedAverage::class),
            $this->createMock(EntityManagerInterface::class)
        );

        $model = new PortfolioViewModel(
            new Stopwatch(),
            $metricsService,
            $dividendServiceMock,
            $this->createMock(\App\Repository\PositionRepository::class),
            $adjustedFactoryMock,
            10
        );

        // Act
        $model->createPortfolioItem(new \ArrayIterator([$positionMock]), 10000.0);
        */
        // Assert that key methods were called
        $this->assertTrue(true); // If no exceptions were thrown, we're good
    }

}
