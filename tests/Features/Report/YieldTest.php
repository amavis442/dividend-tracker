<?php

namespace App\Tests\Features\Report;

use App\Entity\Calendar;
use App\Entity\CorporateAction;
use App\Entity\Currency;
use App\Entity\Position;
use App\Entity\Tax;
use App\Entity\Ticker;
use App\Entity\Transaction;
use PHPUnit\Framework\TestCase;
use App\Service\Dividend\DividendYieldCalculator;
use App\Entity\DividendMonth;
use App\Decorator\AdjustedPositionDecorator;
use App\Service\Transaction\TransactionAdjuster;

/**
 * @test
 *
 * We need a report that shows what the actual dividend yield per company is and the predicted total yield of
 * all the companies. We need to see how much cash is projected to receive per year and how much is invested.
 */
class YieldTest extends TestCase
{
	private function createTransaction(
		float $amount,
		float $price,
		\DateTimeInterface $date,
		int $side
	): Transaction {
		$transaction = new Transaction();
		$transaction->setAmount($amount);
		$transaction->setPrice($price);
		$transaction->setTransactionDate($date);
		$transaction->setSide($side);

		return $transaction;
	}

	private function createTicker(
		int $id,
		string $symbol,
		string $fullname
	): Ticker {
		$ticker = new Ticker();
		$reflection = new \ReflectionClass($ticker);
		$property = $reflection->getProperty('id');
		$property->setAccessible(true);
		$property->setValue($ticker, $id);
		$ticker->setSymbol($symbol);
		$ticker->setFullName($fullname);

		return $ticker;
	}

	private function createPosition(int $id, Ticker $ticker): Position
	{
		$position = new Position(); // Select with ticker id (only open position which should be 1 per ticker)
		$position->setTicker($ticker);
		$reflection = new \ReflectionClass($position);
		$property = $reflection->getProperty('id');
		$property->setAccessible(true);
		$property->setValue($position, $id);

		return $position;
	}

	private function createCorporateAction(
		string $type,
		float $ratio,
		\DateTimeInterface $eventDate
	): CorporateAction {
		$action = new CorporateAction();
		$action->setType($type);
		$action->setRatio($ratio);
		$action->setEventDate($eventDate);

		return $action;
	}

	private function createCalendar(
		Ticker $ticker,
		float $cashAmount,
		\DateTimeInterface $createdAt,
		\DateTimeInterface $exDivDate,
		\DateTimeInterface $paymentDate
	): Calendar {
		$calendar = new Calendar(); // Select with ticker id
		$reflection = new \ReflectionClass($calendar);
		$property = $reflection->getProperty('createdAt');
		$property->setAccessible(true);
		$property->setValue($calendar, $createdAt);
		$calendar->setTicker($ticker);
		$calendar->setExDividendDate($exDivDate);
		$calendar->setPaymentDate($paymentDate);
		$calendar->setCashAmount($cashAmount);
		$calendar->setDividendType(Calendar::REGULAR);

		return $calendar;
	}

	public function test_report_yield_without_corporate_actions(): void
	{
		//Setup
		$ratio = 0.2; // Reverse stock split
		$tickerId = 1;
		$exchangeRates[$tickerId] = 0.9;
		$originalAmount = 10.0;
		$originalPrice = 2.0;
		$adjustedAmount = $originalAmount * $ratio;
		$originalCashAmount = 0.1; // $0.10
		$originalCashAmount2 = 0.6; // $0.10
		$adjustedCashAmount = $originalCashAmount / $ratio; // inverse to amount
		$taxRate = 15; // 15%
		$invested = $originalAmount * $originalPrice;

		$ticker = new Ticker();
		$reflection = new \ReflectionClass($ticker);
		$property = $reflection->getProperty('id');
		$property->setAccessible(true);
		$property->setValue($ticker, 1);
		$ticker->setSymbol('AAPL');
		$ticker->setFullName('Apple computers');

		for ($n = 1; $n < 13; $n++) {
			$dividendMonth = new DividendMonth();
			$dividendMonth->setDividendMonth($n);
			$ticker->addDividendMonth($dividendMonth);
		}

		$currency = new Currency(); // Join with ticker
		$currency->setSymbol('USD');
		$currency->setSign('$');
		$ticker->setCurrency($currency);

		$tax = new Tax(); // Join with ticker
		$tax->setTaxRate($taxRate);
		$tax->setValidFrom(new \DateTime('2025-01-01'));
		$ticker->setTax($tax);

		$calendar = new Calendar(); // Select with ticker id
		$reflection = new \ReflectionClass($calendar);
		$property = $reflection->getProperty('createdAt');
		$property->setAccessible(true);
		$property->setValue($calendar, new \DateTimeImmutable('2025-09-30'));
		$calendar->setTicker($ticker);
		$calendar->setExDividendDate(new \DateTime('2025-10-01'));
		$calendar->setPaymentDate(new \DateTime('2025-10-03'));
		$calendar->setCashAmount($originalCashAmount);
		$calendar->setDividendType(Calendar::REGULAR);

		$position = new Position(); // Select with ticker id (only open position which should be 1 per ticker)
		$position->setTicker($ticker);
		$reflection = new \ReflectionClass($position);
		$property = $reflection->getProperty('id');
		$property->setAccessible(true);
		$property->setValue($position, 1);

		$transaction = new Transaction(); // Select with position id
		$transaction->setPosition($position);
		$transaction->setTransactionDate(new \DateTime('2025-09-01'));
		$transaction->setAmount($originalAmount);
		$transaction->setPrice($originalPrice);

		$position->setAdjustedAmount($transaction->getAdjustedAmount());
		$position->setAllocation($invested);

		// Needed for DividendCalenderService
		$tickers[$ticker->getId()] = $ticker;
		$positions[$ticker->getId()] = $position;
		$transactions[$ticker->getId()] = [];
		$transactions[$ticker->getId()][] = $transaction;
		$calendars[$ticker->getId()] = [$calendar];
		$corporateActions[$ticker->getId()] = [];

		// SUT
		$dividendCalculator = new DividendYieldCalculator();
		$dividendCalculator->load(
			tickers: $tickers,
			calendars: $calendars,
			positions: $positions,
			exchangeRates: $exchangeRates
		);

		$actual = $dividendCalculator->process();

		$cashAmount = $calendar->getAdjustedCashAmount();
		$amount = $transaction->getAdjustedAmount();
		$freq = $ticker->getDividendFrequency();
		$tax = 1 - $ticker->getTax()->getTaxRate();
		$exchangeRate = $exchangeRates[$ticker->getId()];
		$grossYieldPercentage =
			(($freq * $cashAmount * $amount) / $invested) * 100;
		$netYieldPercentage =
			(($freq * $cashAmount * $amount * $exchangeRate * $tax) /
				($invested * $exchangeRate)) *
			100;
		$grossCashYield = $freq * $cashAmount * $amount;
		$netCashYield = $freq * $cashAmount * $amount * $tax * $exchangeRate;

		$expected = [
			$ticker->getId() => [
				'position' => $position,
				'ticker' => $ticker,
				'calendar_used' => $calendar, // Use the lastest
				'total_shares' => $transaction->getAdjustedAmount(),
				'exchange_rate' => $exchangeRates[$ticker->getId()],
				'tax_rate' => $ticker->getTax()->getTaxRate(),
				'currency' => $ticker->getCurrency()->getSymbol(),
				'invested' =>
					$transaction->getAdjustedPrice() *
					$transaction->getAdjustedAmount(), // In dollars
				'yield' => [
					'percentage' => [
						'gross' => $grossYieldPercentage, // percentage ((12 * cash.per_month_per_share.gross / )
						'net' => $netYieldPercentage,
					],
					'cash' => [
						'gross' => $grossCashYield, // Will be in original currency without exchange and tax
						'net' => $netCashYield, // Will be in euro with tax dededucted
					],
				],
				'cash' => [
					'per_month_per_share' => [
						'gross' => $calendar->getAdjustedCashAmount(),
						'net' =>
							$exchangeRates[$ticker->getId()] *
							$calendar->getAdjustedCashAmount() *
							(1 - $ticker->getTax()->getTaxRate()), // exchange_rate * cash_amount_per_month_per_share * (1 - tax_rate)
					],
					'per_month_all_shares' => [
						'gross' =>
							$transaction->getAdjustedAmount() *
							$calendar->getAdjustedCashAmount(), // total_shares * cash.per_month_per_share.gross
						'net' =>
							$calendar->getAdjustedCashAmount() *
							$transaction->getAdjustedAmount() *
							(1 - $ticker->getTax()->getTaxRate()) *
							$exchangeRates[$ticker->getId()], // exchange_rate * total_shares * cash.per_month_per_share.gross * (1 - tax_rate)
					],
				],
			],
		];

		$this->assertEquals($expected, $actual);
	}

	public function test_report_yield_without_corporate_actions_and_multiple_calendars(): void
	{
		//Setup
		$ratio = 0.2; // Reverse stock split
		$tickerId = 1;
		$exchangeRates[$tickerId] = 0.9;
		$originalAmount = 10.0;
		$originalPrice = 2.0;
		$adjustedAmount = $originalAmount * $ratio;
		$originalCashAmount = 0.1; // $0.10
		$originalCashAmount2 = 0.6; // $0.10
		$adjustedCashAmount = $originalCashAmount / $ratio; // inverse to amount
		$taxRate = 15; // 15%
		$invested = $originalAmount * $originalPrice;

		$ticker = new Ticker();
		$reflection = new \ReflectionClass($ticker);
		$property = $reflection->getProperty('id');
		$property->setAccessible(true);
		$property->setValue($ticker, 1);
		$ticker->setSymbol('AAPL');
		$ticker->setFullName('Apple computers');

		for ($n = 1; $n < 13; $n++) {
			$dividendMonth = new DividendMonth();
			$dividendMonth->setDividendMonth($n);
			$ticker->addDividendMonth($dividendMonth);
		}

		$currency = new Currency(); // Join with ticker
		$currency->setSymbol('USD');
		$currency->setSign('$');
		$ticker->setCurrency($currency);

		$tax = new Tax(); // Join with ticker
		$tax->setTaxRate($taxRate);
		$tax->setValidFrom(new \DateTime('2025-01-01'));
		$ticker->setTax($tax);

		$calendar1 = new Calendar(); // Select with ticker id
		$reflection = new \ReflectionClass($calendar1);
		$property = $reflection->getProperty('createdAt');
		$property->setAccessible(true);
		$property->setValue($calendar1, new \DateTimeImmutable('2025-08-30'));
		$calendar1->setTicker($ticker);
		$calendar1->setExDividendDate(new \DateTime('2025-9-01'));
		$calendar1->setPaymentDate(new \DateTime('2025-9-03'));
		$calendar1->setCashAmount($originalCashAmount);
		$calendar1->setDividendType(Calendar::REGULAR);

		$calendar2 = new Calendar(); // Select with ticker id
		$reflection = new \ReflectionClass($calendar2);
		$property = $reflection->getProperty('createdAt');
		$property->setAccessible(true);
		$property->setValue($calendar2, new \DateTimeImmutable('2025-09-30'));
		$calendar2->setTicker($ticker);
		$calendar2->setExDividendDate(new \DateTime('2025-10-01'));
		$calendar2->setPaymentDate(new \DateTime('2025-10-03'));
		$calendar2->setCashAmount($originalCashAmount + 0.02);
		$calendar2->setDividendType(Calendar::REGULAR);

		$position = new Position(); // Select with ticker id (only open position which should be 1 per ticker)
		$position->setTicker($ticker);
		$reflection = new \ReflectionClass($position);
		$property = $reflection->getProperty('id');
		$property->setAccessible(true);
		$property->setValue($position, 1);

		$transaction = new Transaction(); // Select with position id
		$transaction->setPosition($position);
		$transaction->setTransactionDate(new \DateTime('2025-09-01'));
		$transaction->setAmount($originalAmount);
		$transaction->setPrice($originalPrice);

		$position->setAdjustedAmount($transaction->getAdjustedAmount());
		$position->setAllocation($invested);

		// Needed for DividendCalenderService
		$tickers[$ticker->getId()] = $ticker;
		$positions[$ticker->getId()] = $position;
		$transactions[$ticker->getId()] = [];
		$transactions[$ticker->getId()][] = $transaction;
		$calendars[$ticker->getId()] = [$calendar1, $calendar2];
		$corporateActions[$ticker->getId()] = [];

		// SUT
		$dividendCalculator = new DividendYieldCalculator();
		$dividendCalculator->load(
			tickers: $tickers,
			calendars: $calendars,
			positions: $positions,
			exchangeRates: $exchangeRates
		);

		$actual = $dividendCalculator->process();

		// For expected
		$calendar =
			$calendars[$ticker->getId()][
				count($calendars[$ticker->getId()]) - 1
			];
		$cashAmount = $calendar->getAdjustedCashAmount();
		$amount = $transaction->getAdjustedAmount();
		$freq = $ticker->getDividendFrequency();
		$tax = 1 - $ticker->getTax()->getTaxRate();
		$exchangeRate = $exchangeRates[$ticker->getId()];
		$grossYieldPercentage =
			(($freq * $cashAmount * $amount) / $invested) * 100;
		$netYieldPercentage =
			(($freq * $cashAmount * $amount * $exchangeRate * $tax) /
				($invested * $exchangeRate)) *
			100;
		$grossCashYield = $freq * $cashAmount * $amount;
		$netCashYield = $freq * $cashAmount * $amount * $tax * $exchangeRate;

		$expected = [
			$ticker->getId() => [
				'position' => $position,
				'ticker' => $ticker,
				'calendar_used' => $calendar, // Use the lastest
				'total_shares' => $transaction->getAdjustedAmount(),
				'exchange_rate' => $exchangeRates[$ticker->getId()],
				'tax_rate' => $ticker->getTax()->getTaxRate(),
				'currency' => $ticker->getCurrency()->getSymbol(),
				'invested' =>
					$transaction->getAdjustedPrice() *
					$transaction->getAdjustedAmount(), // In dollars
				'yield' => [
					'percentage' => [
						'gross' => $grossYieldPercentage, // percentage ((12 * cash.per_month_per_share.gross / )
						'net' => $netYieldPercentage,
					],
					'cash' => [
						'gross' => $grossCashYield, // Will be in original currency without exchange and tax
						'net' => $netCashYield, // Will be in euro with tax dededucted
					],
				],
				'cash' => [
					'per_month_per_share' => [
						'gross' => $calendar->getAdjustedCashAmount(),
						'net' =>
							$exchangeRates[$ticker->getId()] *
							$calendar->getAdjustedCashAmount() *
							(1 - $ticker->getTax()->getTaxRate()), // exchange_rate * cash_amount_per_month_per_share * (1 - tax_rate)
					],
					'per_month_all_shares' => [
						'gross' =>
							$transaction->getAdjustedAmount() *
							$calendar->getAdjustedCashAmount(), // total_shares * cash.per_month_per_share.gross
						'net' =>
							$calendar->getAdjustedCashAmount() *
							$transaction->getAdjustedAmount() *
							(1 - $ticker->getTax()->getTaxRate()) *
							$exchangeRates[$ticker->getId()], // exchange_rate * total_shares * cash.per_month_per_share.gross * (1 - tax_rate)
					],
				],
			],
		];

		$this->assertEquals($expected, $actual);
	}

	public function test_report_yield_with_corporate_actions_and_multiple_calendars(): void
	{
		//Setup
		$ratio = 0.2; // Reverse stock split
		$tickerId = 1;
		$exchangeRates[1] = 0.9;
		$exchangeRates[2] = 1.0;
		$originalAmount = 10.0;
		$originalPrice = 2.0;
		$adjustedAmount = $originalAmount * $ratio;
		$originalCashAmount = 0.1; // $0.10
		$originalCashAmount2 = 0.6; // $0.10
		$adjustedCashAmount = $originalCashAmount / $ratio; // inverse to amount
		$taxRate = 15; // 15%

		$ticker = $this->createTicker(1, 'AAPL', 'Apple computers');
		$ticker2 = $this->createTicker(2, 'OXLC', 'Oxfordlane Capital');

		for ($n = 1; $n < 13; $n++) {
			$dividendMonth = new DividendMonth();
			$dividendMonth->setDividendMonth($n);
			$ticker->addDividendMonth($dividendMonth);
			$ticker2->addDividendMonth($dividendMonth);
		}

		$currency = new Currency(); // Join with ticker
		$currency->setSymbol('USD');
		$currency->setSign('$');
		$ticker->setCurrency($currency);
		$ticker2->setCurrency($currency);

		$tax = new Tax(); // Join with ticker
		$tax->setTaxRate($taxRate);
		$tax->setValidFrom(new \DateTime('2025-01-01'));
		$ticker->setTax($tax);
		$ticker2->setTax($tax);

		$calendar1 = $this->createCalendar(
			ticker: $ticker,
			cashAmount: $originalCashAmount,
			createdAt: new \DateTimeImmutable('2025-08-30'),
			exDivDate: new \DateTime('2025-9-01'),
			paymentDate: new \DateTime('2025-9-03')
		);

		$calendar2 = $this->createCalendar(
			ticker: $ticker,
			cashAmount: $originalCashAmount,
			createdAt: new \DateTimeImmutable('2025-09-30'),
			exDivDate: new \DateTime('2025-10-01'),
			paymentDate: new \DateTime('2025-10-03')
		);

		$action1 = $this->createCorporateAction(
			'reverse_split',
			0.2,
			new \DateTime('2025-08-11')
		);

		$position = $this->createPosition(1, $ticker);
		$position2 = $this->createPosition(2, $ticker2);

		$tx1 = $this->createTransaction(
			100.0,
			10.0,
			new \DateTime('2024-06-10'),
			Transaction::BUY
		); // (100 * 0.2) = 20
		$tx2 = $this->createTransaction(
			50.0,
			12.0,
			new \DateTime('2025-06-10'),
			Transaction::BUY
		); // 50 * 0.2 = 10
		$tx3 = $this->createTransaction(
			50.0,
			15.0,
			new \DateTime('2025-08-10'),
			Transaction::BUY
		); // 50 * 0.2 = 10 -> total should be 60
		$invested = 100 * 10 + 50 * 12 + 50 * 15;

		$tx1->setPosition($position);
		$tx2->setPosition($position);
		$tx3->setPosition($position);

		$position->setAllocation($invested);

		$tx4 = $this->createTransaction(
			500.0,
			10.0,
			new \DateTime('2024-06-10'),
			Transaction::BUY
		); // (500 * 0.2) = 100
		$tx5 = $this->createTransaction(
			150.0,
			12.0,
			new \DateTime('2025-06-10'),
			Transaction::BUY
		); // 150 * 0.2 = 30
		$tx6 = $this->createTransaction(
			50.0,
			15.0,
			new \DateTime('2025-08-10'),
			Transaction::BUY
		); // 50 * 0.2 = 10 -> total should be 140
		$invested2 = 500 * 10 + 150 * 12 + 50 * 15;
		$position2->setAllocation($invested2);
		$tx1->setPosition($position2);
		$tx2->setPosition($position2);
		$tx3->setPosition($position2);

		$tickers[$ticker->getId()] = $ticker;
		$transactions[$ticker->getId()] = [$tx1, $tx2, $tx3];
		$calendars[$ticker->getId()] = [$calendar1, $calendar2];
		$corporateActions[$ticker->getId()] = [$action1];

		$tickers[$ticker2->getId()] = $ticker2;
		$transactions[$ticker2->getId()] = [$tx4, $tx5, $tx6];
		$calendars[$ticker2->getId()] = [$calendar1, $calendar2];
		$corporateActions[$ticker2->getId()] = [$action1];

		$adjuster = new TransactionAdjuster();
		$decorator = new AdjustedPositionDecorator(
			position: $position,
			transactions: $transactions[$ticker->getId()],
			actions: $corporateActions[$ticker->getId()],
			transactionAdjuster: $adjuster
		);
		$amount = $decorator->getAdjustedAmount();
		$position->setAdjustedAmount($amount);
		$positions[$ticker->getId()] = $position;

		$this->assertEquals(40, $amount);

		$decorator2 = new AdjustedPositionDecorator(
			position: $position2,
			transactions: $transactions[$ticker2->getId()],
			actions: $corporateActions[$ticker2->getId()],
			transactionAdjuster: $adjuster
		);
		$amount2 = $decorator2->getAdjustedAmount();
		$position2->setAdjustedAmount($amount2);
		$positions[$ticker2->getId()] = $position2;

		$this->assertEquals(140, $amount2);

		// SUT
		$dividendCalculator = new DividendYieldCalculator();

		$dividendCalculator->load(
			tickers: $tickers,
			calendars: $calendars,
			positions: $positions,
			exchangeRates: $exchangeRates
		);

		$actual = $dividendCalculator->process();

		// For expected
		$calendar =
			$calendars[$ticker->getId()][
				count($calendars[$ticker->getId()]) - 1
			];
		$cashAmount = $calendar->getAdjustedCashAmount();

		// For ticker 1
		$freq = $ticker->getDividendFrequency();
		$tax = 1 - $ticker->getTax()->getTaxRate();
		$exchangeRate = $exchangeRates[$ticker->getId()];
		$grossYieldPercentage =
			(($freq * $cashAmount * $amount) / $invested) * 100;
		$netYieldPercentage =
			(($freq * $cashAmount * $amount * $exchangeRate * $tax) /
				($invested * $exchangeRate)) *
			100;
		$grossCashYield = $freq * $cashAmount * $amount;
		$netCashYield = $freq * $cashAmount * $amount * $tax * $exchangeRate;

		$expected[$ticker->getId()] = [
			'position' => $position,
			'ticker' => $ticker,
			'calendar_used' => $calendar, // Use the lastest
			'total_shares' => $position->getAdjustedAmount(),
			'exchange_rate' => $exchangeRates[$ticker->getId()],
			'tax_rate' => $ticker->getTax()->getTaxRate(),
			'currency' => $ticker->getCurrency()->getSymbol(),
			'invested' => $position->getAllocation(), // In dollars
			'yield' => [
				'percentage' => [
					'gross' => $grossYieldPercentage, // percentage ((12 * cash.per_month_per_share.gross / )
					'net' => $netYieldPercentage,
				],
				'cash' => [
					'gross' => $grossCashYield, // Will be in original currency without exchange and tax
					'net' => $netCashYield, // Will be in euro with tax dededucted
				],
			],
			'cash' => [
				'per_month_per_share' => [
					'gross' => $calendar->getAdjustedCashAmount(),
					'net' =>
						$exchangeRates[$ticker->getId()] *
						$calendar->getAdjustedCashAmount() *
						(1 - $ticker->getTax()->getTaxRate()), // exchange_rate * cash_amount_per_month_per_share * (1 - tax_rate)
				],
				'per_month_all_shares' => [
					'gross' =>
						$position->getAdjustedAmount() *
						$calendar->getAdjustedCashAmount(), // total_shares * cash.per_month_per_share.gross
					'net' =>
						$calendar->getAdjustedCashAmount() *
						$position->getAdjustedAmount() *
						(1 - $ticker->getTax()->getTaxRate()) *
						$exchangeRates[$ticker->getId()], // exchange_rate * total_shares * cash.per_month_per_share.gross * (1 - tax_rate)
				],
			],
		];

		// For ticker 2
		$freq2 = $ticker2->getDividendFrequency();
		$tax2 = 1 - $ticker2->getTax()->getTaxRate();
		$exchangeRate2 = $exchangeRates[$ticker2->getId()];
		$grossYieldPercentage2 =
			(($freq2 * $cashAmount * $amount2) / $invested2) * 100;
		$netYieldPercentage2 =
			(($freq2 * $cashAmount * $amount2 * $exchangeRate2 * $tax2) /
				($invested2 * $exchangeRate2)) *
			100;
		$grossCashYield2 = $freq2 * $cashAmount * $amount2;
		$netCashYield2 =
			$freq2 * $cashAmount * $amount2 * $tax2 * $exchangeRate2;

		$expected[$ticker2->getId()] = [
			'position' => $position2,
			'ticker' => $ticker2,
			'calendar_used' => $calendar, // Use the lastest
			'total_shares' => $position2->getAdjustedAmount(),
			'exchange_rate' => $exchangeRates[$ticker2->getId()],
			'tax_rate' => $ticker2->getTax()->getTaxRate(),
			'currency' => $ticker2->getCurrency()->getSymbol(),
			'invested' => $position2->getAllocation(), // In dollars
			'yield' => [
				'percentage' => [
					'gross' => $grossYieldPercentage2, // percentage ((12 * cash.per_month_per_share.gross / )
					'net' => $netYieldPercentage2,
				],
				'cash' => [
					'gross' => $grossCashYield2, // Will be in original currency without exchange and tax
					'net' => $netCashYield2, // Will be in euro with tax dededucted
				],
			],
			'cash' => [
				'per_month_per_share' => [
					'gross' => $calendar->getAdjustedCashAmount(),
					'net' =>
						$exchangeRates[$ticker2->getId()] *
						$calendar->getAdjustedCashAmount() *
						(1 - $ticker2->getTax()->getTaxRate()), // exchange_rate * cash_amount_per_month_per_share * (1 - tax_rate)
				],
				'per_month_all_shares' => [
					'gross' =>
						$position2->getAdjustedAmount() *
						$calendar->getAdjustedCashAmount(), // total_shares * cash.per_month_per_share.gross
					'net' =>
						$calendar->getAdjustedCashAmount() *
						$position2->getAdjustedAmount() *
						(1 - $ticker2->getTax()->getTaxRate()) *
						$exchangeRates[$ticker2->getId()], // exchange_rate * total_shares * cash.per_month_per_share.gross * (1 - tax_rate)
				],
			],
		];

		$this->assertEquals($expected, $actual);
	}
}
