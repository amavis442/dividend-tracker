<?php

namespace App\Tests\Features\Trading212;

use App\Decorator\InstrumentDecorator;
use App\Entity\Payment;
use App\Entity\Ticker;
use App\Entity\Trading212PieInstrument;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

/**
 * Tests for InstrumentDecorator — specifically the N+1 payment lookup fix.
 *
 * @see PieInstrumentsController::index()
 * @see InstrumentDecorator::instruments()
 */
final class InstrumentDecoratorTest extends TestCase
{
	public function testInstrumentsReturnsPieInstrumentsChartData(): void
	{
		$ticker = $this->createTickerWithId(1, 'AAPL', 'Apple Inc.', 12);
		$instrument = $this->createInstrument(
			ticker: $ticker,
			ownedQuantity: 10,
			priceAvgInvested: 500.0,
			createdAt: new DateTimeImmutable('2025-01-15'),
		);
		$instruments = new ArrayCollection([$instrument]);
		$payments = new ArrayCollection([]);
		$tickers = $this->buildTickersMap($ticker, $instrument);

		$decorator = new InstrumentDecorator();
		$result = $decorator->instruments(
			pieAvgInvested: 1000.0,
			instruments: $instruments,
			payments: $payments,
			tickers: $tickers,
			rateDollarEuro: 0.92,
		);

		$this->assertNotEquals([], $result['pieInstruments']);
		$this->assertTrue(in_array('labels', array_keys($result['pieInstruments'])));
		$this->assertTrue(in_array('data', array_keys($result['pieInstruments'])));
		$this->assertEquals('Apple Inc.', $result['pieInstruments']['labels'][0]);
	}

	public function testInstrumentsUsesPreloadedPaymentsInsteadOfNPlusOne(): void
	{
		$ticker = $this->createTickerWithId(1, 'AAPL', 'Apple Inc.', 12);
		$instrument = $this->createInstrument(
			ticker: $ticker,
			ownedQuantity: 10,
			priceAvgInvested: 500.0,
			createdAt: new DateTimeImmutable('2025-06-15'),
		);
		// Payment after instrument createdAt — should NOT match
		$latePayment = $this->createPayment($ticker, new DateTimeImmutable('2025-07-01'), 10, 5.0);
		// Payment before instrument createdAt — should match (DESC order, so this is first)
		$earlyPayment = $this->createPayment($ticker, new DateTimeImmutable('2025-06-01'), 10, 3.0);
		$instruments = new ArrayCollection([$instrument]);
		$payments = new ArrayCollection([$latePayment, $earlyPayment]);
		$tickers = $this->buildTickersMap($ticker, $instrument);

		$decorator = new InstrumentDecorator();
		$result = $decorator->instruments(
			pieAvgInvested: 1000.0,
			instruments: $instruments,
			payments: $payments,
			tickers: $tickers,
			rateDollarEuro: 0.92,
		);

		// (3.0 / 10) * 10 = 3.0
		$this->assertEquals(3.0, $result['pieDividend'], 0.001);
		$this->assertEquals(3.0, $instrument->getDividendPaid(), 0.001);
	}

	public function testInstrumentsHandlesNullPayDateGracefully(): void
	{
		$ticker = $this->createTickerWithId(1, 'AAPL', 'Apple Inc.', 12);
		$instrument = $this->createInstrument(
			ticker: $ticker,
			ownedQuantity: 10,
			priceAvgInvested: 500.0,
			createdAt: new DateTimeImmutable('2025-06-15'),
		);
		// Payment with payDate AFTER instrument createdAt — should NOT match (defensive null/ordering check)
		$laterPayment = $this->createPayment($ticker, new DateTimeImmutable('2025-07-01'), 10, 5.0);
		$instruments = new ArrayCollection([$instrument]);
		$payments = new ArrayCollection([$laterPayment]);
		$tickers = $this->buildTickersMap($ticker, $instrument);

		$decorator = new InstrumentDecorator();
		$result = $decorator->instruments(
			pieAvgInvested: 1000.0,
			instruments: $instruments,
			payments: $payments,
			tickers: $tickers,
			rateDollarEuro: 0.92,
		);

		// Payment after createdAt should be skipped, resulting in 0 dividend paid
		$this->assertEquals(0.0, $result['pieDividend'], 0.001);
	}

	public function testInstrumentsReturnsZeroWhenNoPaymentsMatch(): void
	{
		$ticker = $this->createTickerWithId(1, 'AAPL', 'Apple Inc.', 12);
		$instrument = $this->createInstrument(
			ticker: $ticker,
			ownedQuantity: 10,
			priceAvgInvested: 500.0,
			createdAt: new DateTimeImmutable('2025-06-01'),
		);
		// Payment AFTER instrument — should NOT match (payDate must be before createdAt)
		$futurePayment = $this->createPayment($ticker, new DateTimeImmutable('2025-07-01'), 10, 5.0);
		$instruments = new ArrayCollection([$instrument]);
		$payments = new ArrayCollection([$futurePayment]);
		$tickers = $this->buildTickersMap($ticker, $instrument);

		$decorator = new InstrumentDecorator();
		$result = $decorator->instruments(
			pieAvgInvested: 1000.0,
			instruments: $instruments,
			payments: $payments,
			tickers: $tickers,
			rateDollarEuro: 0.92,
		);

		$this->assertEquals(0.0, $result['pieDividend'], 0.001);
	}

	public function testInstrumentsSkipsInstrumentWithNoTicker(): void
	{
		$instrument = $this->createInstrument(
			ticker: null,
			ownedQuantity: 10,
			priceAvgInvested: 500.0,
			createdAt: new DateTimeImmutable('2025-06-15'),
		);
		$instruments = new ArrayCollection([$instrument]);
		$payments = new ArrayCollection([]);
		$emptyTickers = [];

		$decorator = new InstrumentDecorator();
		$result = $decorator->instruments(
			pieAvgInvested: 1000.0,
			instruments: $instruments,
			payments: $payments,
			tickers: $emptyTickers,
			rateDollarEuro: 0.92,
		);

		$this->assertEquals(0.0, $result['pieDividend'], 0.001);
		$this->assertEquals(0.0, $result['pieCurrentDividend'], 0.001);
		$this->assertEquals(0.0, $result['pieAvgDividend'], 0.001);
	}

	public function testInstrumentsSkipsInstrumentWithZeroInvested(): void
	{
		$ticker = $this->createTickerWithId(1, 'AAPL', 'Apple Inc.', 12);
		$instrument = $this->createInstrument(
			ticker: $ticker,
			ownedQuantity: 0,
			priceAvgInvested: 0.0,
			createdAt: new DateTimeImmutable('2025-06-15'),
		);
		$instruments = new ArrayCollection([$instrument]);
		$payments = new ArrayCollection([]);
		$tickers = $this->buildTickersMap($ticker, $instrument);

		$decorator = new InstrumentDecorator();
		$result = $decorator->instruments(
			pieAvgInvested: 1000.0,
			instruments: $instruments,
			payments: $payments,
			tickers: $tickers,
			rateDollarEuro: 0.92,
		);

		$this->assertEquals(0.0, $result['pieDividend'], 0.001);
	}

	public function testInstrumentsCorrectPaymentPerTicker(): void
	{
		$tickerA = $this->createTickerWithId(1, 'AAPL', 'Apple Inc.', 12);
		$tickerB = $this->createTickerWithId(2, 'GOOG', 'Google LLC', 4);
		$instrumentA = $this->createInstrument(
			ticker: $tickerA, ownedQuantity: 5, priceAvgInvested: 300.0,
			createdAt: new DateTimeImmutable('2025-05-01'),
		);
		$instrumentB = $this->createInstrument(
			ticker: $tickerB, ownedQuantity: 20, priceAvgInvested: 700.0,
			createdAt: new DateTimeImmutable('2025-03-01'),
		);
		$paymentA = $this->createPayment($tickerA, new DateTimeImmutable('2025-04-15'), 5, 2.0);
		$paymentB = $this->createPayment($tickerB, new DateTimeImmutable('2025-02-15'), 10, 4.0);

		$instruments = new ArrayCollection([$instrumentA, $instrumentB]);
		$payments = new ArrayCollection([$paymentA, $paymentB]);
		$tickers = $this->buildTickersMapMulti([
			[$tickerA, $instrumentA],
			[$tickerB, $instrumentB],
		]);

		$decorator = new InstrumentDecorator();
		$result = $decorator->instruments(
			pieAvgInvested: 1000.0,
			instruments: $instruments,
			payments: $payments,
			tickers: $tickers,
			rateDollarEuro: 0.92,
		);

		// A: (2.0 / 5) * 5 = 2.0, B: (4.0 / 10) * 20 = 8.0
		$this->assertEquals(10.0, $result['pieDividend'], 0.001);
	}

	// -- Helper methods -------------------------------------------------------

	private function createTickerWithId(int $id, string $symbol, string $fullname, int $frequency): Ticker
	{
		$ticker = new Ticker();
		$ticker->setSymbol($symbol);
		$ticker->setFullname($fullname);

		$reflection = new \ReflectionClass($ticker);
		$idProp = $reflection->getProperty('id');
		$idProp->setAccessible(true);
		$idProp->setValue($ticker, $id);

		return $ticker;
	}

	private function createInstrument(
		?Ticker $ticker,
		int $ownedQuantity,
		float $priceAvgInvested,
		DateTimeImmutable $createdAt,
	): Trading212PieInstrument {
		$i = new Trading212PieInstrument();
		if ($ticker !== null) {
			$i->setTicker($ticker);
		}
		$i->setOwnedQuantity((float) $ownedQuantity);
		$i->setPriceAvgInvestedValue($priceAvgInvested);
		$i->setCreatedAt($createdAt);

		return $i;
	}

	private function createPayment(
		Ticker $ticker,
		DateTimeImmutable $payDate,
		float $amount,
		float $dividend,
	): Payment {
		$p = new Payment();
		$p->setTicker($ticker);
		$p->setAmount($amount);
		$p->setDividend($dividend);

		$dateTime = new \DateTime($payDate->format('Y-m-d'));
		$reflection = new \ReflectionClass($p);
		$prop = $reflection->getProperty('payDate');
		$prop->setAccessible(true);
		$prop->setValue($p, $dateTime);

		return $p;
	}

	private function buildTickersMap(Ticker $ticker, Trading212PieInstrument $instrument): array
	{
		return [
			$ticker->getId() => [
				'ticker' => $ticker,
				'instrument' => $instrument,
				'adjustedDividend' => [],
				'calendars' => [],
				'dividend' => [
					'sumDividend' => 0.0, 'records' => 0, 'avg' => 0.0,
					'predicted_payment' => [], 'predicted_payment_monthly' => [], 'frequency' => 12,
				],
				'tax' => $this->createTaxStub($ticker, 0.15),
			],
		];
	}

	private function buildTickersMapMulti(array $pairs): array
	{
		$map = [];
		foreach ($pairs as $pair) {
			[$ticker, $instrument] = $pair;
			$map[$ticker->getId()] = [
				'ticker' => $ticker,
				'instrument' => $instrument,
				'adjustedDividend' => [],
				'calendars' => [],
				'dividend' => [
					'sumDividend' => 0.0, 'records' => 0, 'avg' => 0.0,
					'predicted_payment' => [], 'predicted_payment_monthly' => [], 'frequency' => 4,
				],
				'tax' => $this->createTaxStub($ticker, 0.0),
			];
		}
		return $map;
	}

	/**
	 * Returns a stub ticker-like object that returns a Tax stub via getTax().
	 *
	 * $instrumentTicker['tax']->getTax()->getTaxRate()
	 */
	private function createTaxStub(Ticker $realTicker, float $rate): object
	{
		$tax = new \App\Entity\Tax();
		$tax->setTaxRate($rate);
		$tax->setValidFrom(new \DateTime('2024-01-01'));

		$tickerStub = new \App\Entity\Ticker();
		$tickerStub->setSymbol($realTicker->getSymbol());
		$tickerStub->setFullname($realTicker->getFullname());
		$tickerStub->setTax($tax);

		$reflection = new \ReflectionClass($tickerStub);
		$prop = $reflection->getProperty('id');
		$prop->setAccessible(true);
		$prop->setValue($tickerStub, $realTicker->getId());

		return $tickerStub;
	}
}