<?php

namespace App\Tests\Unit\Service;

use App\Dto\ExchangeTaxDto;
use App\Entity\Calendar;
use App\Entity\Currency;
use App\Entity\Pie;
use App\Entity\Tax;
use App\Entity\Ticker;
use App\Entity\Trading212PieInstrument;
use App\Entity\Trading212PieMetaData;
use App\Repository\DividendCalendarRepository;
use App\Repository\Trading212PieInstrumentRepository;
use App\Service\Dividend\DividendAdjuster;
use App\Service\Dividend\DividendForecastService;
use App\Service\ExchangeRate\ExchangeAndTaxResolver;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use App\DataProvider\DividendDataProvider;
use App\DataProvider\CorporateActionDataProvider;
use App\Decorator\Factory\AdjustedDividendDecoratorFactory;

class DividendForecastServiceTest extends TestCase
{
	public function testCalculateProjectedPayoutsReturnsCorrectPayouts(): void
	{
		$snapshotDate = new \DateTime('2025-07-27');

		$pie = new Pie();
		$pie->setLabel('test pie');
		$metaDataMock = $this->createConfiguredMock(
			Trading212PieMetaData::class,
			[
				'getPie' => $pie,
			]
		);

		$taxEntity = $this->createConfiguredMock(Tax::class, [
			'getTaxRate' => 0.25,
		]);

		$action = $this->createMock(\App\Entity\CorporateAction::class);
		$action
			->method('getEventDate')
			->willReturn(new \DateTime('2025-07-20'));
		$action->method('getRatio')->willReturn(0.5);

		$position = $this->createMock(\App\Entity\Position::class);

		$tickerId = 1;
		$tickerEntity = $this->createConfiguredMock(Ticker::class, [
			'getId' => $tickerId,
			'getSymbol' => 'AAPL',
			'getTax' => $taxEntity,
			'getPositions' => new ArrayCollection([$position]),
			'getCorporateActions' => new ArrayCollection([$action]),
		]);

		$snapshot = $this->createConfiguredMock(
			Trading212PieInstrument::class,
			[
				'getTicker' => $tickerEntity,
				'getCreatedAt' => new \DateTimeImmutable('2025-07-01'),
				'getOwnedQuantity' => 100.0,
				'getTrading212PieMetaData' => $metaDataMock,
			]
		);

		$currency = $this->createConfiguredMock(Currency::class, [
			'getSymbol' => 'USD',
		]);

		$calId = 1;
		$calendarMock = $this->createConfiguredMock(Calendar::class, [
			'getId' => $calId,
			'getExDividendDate' => new \DateTime('2025-07-15'),
			'getCashAmount' => 0.85,
			'getPaymentDate' => new \DateTime('2025-08-15'),
			'getCurrency' => $currency,
			'getCreatedAt' => new \DateTimeImmutable('2025-06-30'),
			'getTicker' => $tickerEntity,
		]);

		$exchangeTaxDto = new ExchangeTaxDto(
			taxAmount: 0.15,
			exchangeRate: 1.05,
			currency: 'USD'
		);

		$holdingsRepo = $this->createMock(
			Trading212PieInstrumentRepository::class
		);
		$holdingsRepo->method('getSnapshotsByDate')->willReturn([$snapshot]);

		$calendarRepo = $this->createMock(DividendCalendarRepository::class);
		$calendarRepo
			->method('getEntriesByTickerAndPayoutDate')
			->willReturn([$calendarMock]);

		$exchangeAndTaxResolver = $this->createMock(ExchangeAndTaxResolver::class);
		$exchangeAndTaxResolver
			->method('resolve')
			->with($tickerEntity, $calendarMock)
			->willReturn($exchangeTaxDto);

		$dividendAdjust = $this->createMock(DividendAdjuster::class);
		$dividendAdjust
			->method('getAdjustedDividend')
			->with(
				$calendarMock,
				[$action]
			)
			->willReturn(1.7);

		$actionsProvider = $this->createConfiguredMock(
			CorporateActionDataProvider::class,
			[
				'load' => [$tickerId => [$action]],
			]
		);

		$dividendProvider = $this->createConfiguredMock(
			DividendDataProvider::class,
			[
				'load' => [$tickerId => [$calId => $calendarMock]],
			]
		);

		$adjustedDividendDecoratorFactory = new AdjustedDividendDecoratorFactory($dividendAdjust);

		$service = new DividendForecastService(
			$holdingsRepo,
			$calendarRepo,
			$exchangeAndTaxResolver,
			$dividendAdjust,
			$adjustedDividendDecoratorFactory,
			$dividendProvider,
			$actionsProvider
		);

		$result = $service->calculateProjectedPayouts($snapshotDate);

		$expectedAmount = 100 * 1.7 * (1 - 0.15) * 1.05;

		$this->assertCount(1, $result);
		$this->assertSame('AAPL', $result[0]['ticker']);
		$this->assertEquals($expectedAmount, $result[0]['payout']);
		$this->assertSame('USD', $result[0]['currency']);
		$this->assertEquals(
			'2025-08-15',
			$result[0]['paymentDate']->format('Y-m-d')
		);
		$this->assertEquals('test pie', $result[0]['pieLabel']);
	}
}
