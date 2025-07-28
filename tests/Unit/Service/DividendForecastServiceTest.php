<?php

namespace App\Tests\Unit\Service;

use App\Dto\ExchangeTaxDto;
use App\Entity\Calendar;
use App\Entity\Currency;
use App\Entity\Tax;
use App\Entity\Ticker;
use App\Entity\Trading212PieInstrument;
use App\Repository\Trading212PieInstrumentRepository;
use App\Repository\DividendCalendarRepository;
use App\Service\ExchangeAndTaxResolver;
use App\Service\DividendForecastService;
use PHPUnit\Framework\TestCase;
use App\Entity\Pie;
use App\Entity\Trading212PieMetaData;

class DividendForecastServiceTest extends TestCase
{
	public function testCalculateProjectedPayoutsReturnsCorrectPayouts(): void
	{
		$snapshotDate = new \DateTime('2025-07-27');

		$pie = new Pie();
		$pie->setLabel('test pie');
		$metaDataMock = $this->createConfiguredMock(Trading212PieMetaData::class,[
			'getPie' => $pie
		]);



        $taxEntity = $this->createConfiguredMock(Tax::class, [
			'getTaxRate' => 0.25,
		]);

		$tickerEntity = $this->createMock(Ticker::class);
		$tickerEntity->method('getTax')->willReturn($taxEntity);

		$tickerEntity = $this->createConfiguredMock(Ticker::class, [
			'getSymbol' => 'AAPL',
		]);

		$snapshot = $this->createMock(Trading212PieInstrument::class);
		$snapshot->method('getTicker')->willReturn($tickerEntity);
		$snapshot->method('getTrading212PieMetaData')->willReturn($metaDataMock);

		$snapshot
			->method('getCreatedAt')
			->willReturn(new \DateTimeImmutable('2025-07-01'));
		$snapshot->method('getOwnedQuantity')->willReturn(100.0);

		$entry = $this->createMock(Calendar::class);
		$entry
			->method('getExDividendDate')
			->willReturn(new \DateTime('2025-07-15'));
		$entry->method('getCashAmount')->willReturn(0.85);
		$entry
			->method('getPaymentDate')
			->willReturn(new \DateTime('2025-08-15'));
		$currency = $this->createConfiguredMock(Currency::class, [
			'getSymbol' => 'USD',
		]);
		$entry->method('getCurrency')->willReturn($currency);

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
			->willReturn([$entry]);

		$resolver = $this->createMock(ExchangeAndTaxResolver::class);
		$resolver
			->method('resolve')
			->with($tickerEntity, $entry)
			->willReturn($exchangeTaxDto);

		$service = new DividendForecastService(
			$holdingsRepo,
			$calendarRepo,
			$resolver
		);

		$result = $service->calculateProjectedPayouts($snapshotDate);

		$expectedAmount = 100 * 0.85 * (1 - 0.15) * 1.05;

		$this->assertCount(1, $result);
        $this->assertEquals('AAPL', $result[0]['ticker']);
		$this->assertEquals($expectedAmount, $result[0]['payout']);
		$this->assertEquals('USD', $result[0]['currency']);
	}
}
