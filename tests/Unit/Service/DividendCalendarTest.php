<?php

namespace App\Tests\Unit\Service;

use App\Decorator\Factory\AdjustedPositionDecoratorFactory;
use App\Entity\Calendar;
use App\Entity\CorporateAction;
use App\Entity\Currency;
use App\Entity\Position;
use App\Entity\Tax;
use App\Entity\Ticker;
use App\Entity\Transaction;
use App\Service\Dividend\DividendCalendarService;
use App\Service\Transaction\ShareEligibilityCalculator;
use App\Service\Transaction\TransactionAdjuster;
use App\Service\Dividend\DividendAdjuster;

use PHPUnit\Framework\TestCase;

/**
 * Behaviour/requirement: We want to produce a calendar that we can print out with
 * dates when a company pays a dividend. We need to know how many stocks are eligable because of
 * the e dividend date, how much the dividend per share is, how much it will pay in total in the selected
 * currency. If a share pays in USD, then it also needs to convert this into EUR. If you have to pay dividend
 * tax, then the end total needs to be reduced by that amount. Basically you want to know how much will be added
 * to your bankaccount on different dates.
 */
class DividendCalendarTest extends TestCase
{
	/**
	 * This test should be its own test class.
	 */
	public function testAdjustedAmount(): void
	{
		//Setup
		$ratio = 0.2; // Reverse stock split
		$exchangeRate = 0.89;
		$originalAmount = 10.0;
		$adjustedAmount = $originalAmount * $ratio;
		$originalCashAmount = 0.1; // $0.10
		$adjustedCashAmount = $originalCashAmount / $ratio; // inverse to amount
		$taxRate = 15; // 15%

		$ticker = new Ticker();
		$reflection = new \ReflectionClass($ticker);
		$property = $reflection->getProperty('id');
		$property->setAccessible(true);
		$property->setValue($ticker, 1);
		$ticker->setSymbol('AAPL');
		$ticker->setFullName('Apple computers');

		$currency = new Currency(); // Join with ticker
		$currency->setSymbol('USD');
		$currency->setSign('$');
		$ticker->setCurrency($currency);

		$tax = new Tax(); // Join with ticker
		$tax->setTaxRate($taxRate);
		$tax->setValidFrom(new \DateTime('2025-01-01'));
		$ticker->setTax($tax);

		$calendar = new Calendar(); // Select with ticker id
		$calendar->setTicker($ticker);
		$calendar->setExDividendDate(new \DateTime('2025-10-01'));
		$calendar->setPaymentDate(new \DateTime('2025-10-03'));
		$calendar->setCashAmount($originalCashAmount);
		$calendar->setDividendType(Calendar::REGULAR);
		$reflection = new \ReflectionClass($calendar);
		$property = $reflection->getProperty('createdAt');
		$property->setAccessible(true);
		$property->setValue($calendar, new \DateTimeImmutable('2025-09-30'));

		$corporateAction = new CorporateAction(); // Select with ticker id
		$corporateAction->setTicker($ticker);
		$corporateAction->setRatio(0.2);
		$corporateAction->setEventDate(new \DateTime('2025-10-01'));

		$position = new Position(); // Select with ticker id (only open position which should be 1 per ticker)
		$position->setTicker($ticker);

		$transaction = new Transaction(); // Select with position id
		$transaction->setPosition($position);
		$transaction->setTransactionDate(new \DateTime('2025-09-01'));
		$transaction->setAmount($originalAmount);

		// Needed for DividendCalenderService
		$tickers[$ticker->getId()] = $ticker;
		$transactions[$ticker->getId()] = [];
		$transactions[$ticker->getId()][] = $transaction;

		$calendars[$ticker->getId()][] = $calendar;
		$corporateActions[$ticker->getId()][] = $corporateAction;

		$this->computeAdjustedAmount(
			$transactions,
			$calendars,
			$corporateActions
		);
		$tickerId = 1;

		$transaction = $transactions[$tickerId][0];
		$this->assertEquals(2, $transaction->getAdjustedAmount());

		$calendar = $calendars[$tickerId][0];
		$this->assertEquals(0.5, $calendar->getAdjustedCashAmount());
	}

	private function computeAdjustedAmount(
		array $transactions,
		array $calendars,
		array $corporateActions
	): void {
		$tickerId = 1;
		$transactionAdjuster = new TransactionAdjuster();

		foreach ($transactions[$tickerId] as $transaction) {
			$transactionAdjuster->getAdjustedAmount(
				$transaction,
				$corporateActions[$tickerId]
			);
		}

		$dividendAdjuster = new DividendAdjuster();
		foreach ($calendars[$tickerId] as $calendar) {
			$dividendAdjuster->getAdjustedDividend(
				$calendar,
				$corporateActions[$tickerId]
			);
		}
	}

	public function testDividendCalendar(): void
	{
		//Setup
		$ratio = 0.2; // Reverse stock split
		$tickerId = 1;
		$exchangeRates[$tickerId] = 0.89;
		$originalAmount = 10.0;
		$adjustedAmount = $originalAmount * $ratio;
		$originalCashAmount = 0.1; // $0.10
		$originalCashAmount2 = 0.6; // $0.10
		$adjustedCashAmount = $originalCashAmount / $ratio; // inverse to amount
		$taxRate = 15; // 15%

		$ticker = new Ticker();
		$reflection = new \ReflectionClass($ticker);
		$property = $reflection->getProperty('id');
		$property->setAccessible(true);
		$property->setValue($ticker, 1);
		$ticker->setSymbol('AAPL');
		$ticker->setFullName('Apple computers');

		$currency = new Currency(); // Join with ticker
		$currency->setSymbol('USD');
		$currency->setSign('$');
		$ticker->setCurrency($currency);

		$tax = new Tax(); // Join with ticker
		$tax->setTaxRate($taxRate);
		$tax->setValidFrom(new \DateTime('2025-01-01'));
		$ticker->setTax($tax);

		$calendar = new Calendar(); // Select with ticker id
		$calendar->setTicker($ticker);
		$calendar->setExDividendDate(new \DateTime('2025-10-01'));
		$calendar->setPaymentDate(new \DateTime('2025-10-03'));
		$calendar->setCashAmount($originalCashAmount);
		$calendar->setDividendType(Calendar::REGULAR);
		$reflection = new \ReflectionClass($calendar);
		$property = $reflection->getProperty('createdAt');
		$property->setAccessible(true);
		$property->setValue($calendar, new \DateTimeImmutable('2025-09-30'));

		$calendar2 = new Calendar(); // Select with ticker id
		$calendar2->setTicker($ticker);
		$calendar2->setExDividendDate(new \DateTime('2025-11-01'));
		$calendar2->setPaymentDate(new \DateTime('2025-11-03'));
		$calendar2->setCashAmount($originalCashAmount2);
		$calendar2->setDividendType(Calendar::REGULAR);
		$reflection = new \ReflectionClass($calendar2);
		$property = $reflection->getProperty('createdAt');
		$property->setAccessible(true);
		$property->setValue($calendar2, new \DateTimeImmutable('2025-10-30'));

		$corporateAction = new CorporateAction(); // Select with ticker id
		$corporateAction->setTicker($ticker);
		$corporateAction->setRatio(0.2);
		$corporateAction->setEventDate(new \DateTime('2025-10-01'));

		$position = new Position(); // Select with ticker id (only open position which should be 1 per ticker)
		$position->setTicker($ticker);
		$reflection = new \ReflectionClass($position);
		$property = $reflection->getProperty('id');
		$property->setAccessible(true);
		$property->setValue($position, 1);
		$positions[$ticker->getId()] = $position->getId();

		$transaction = new Transaction(); // Select with position id
		$transaction->setPosition($position);
		$transaction->setTransactionDate(new \DateTime('2025-09-01'));
		$transaction->setAmount($originalAmount);

		// Needed for DividendCalenderService
		$tickers[$ticker->getId()] = $ticker;
		$transactions[$ticker->getId()] = [];
		$transactions[$ticker->getId()][] = $transaction;

		$calendars[$ticker->getId()] = [$calendar, $calendar2];

		$corporateActions[$ticker->getId()][] = $corporateAction;

		$this->computeAdjustedAmount(
			$transactions,
			$calendars,
			$corporateActions
		);

		$shareEligibilityCalculator = new ShareEligibilityCalculator();
		$dividendCalendarService = new DividendCalendarService(
			$shareEligibilityCalculator
		);

		$expected = [
			'202510' => [
				// Payment date year and month
				'2025-10-03' => [
					// Payment date
					[
						'ticker' => [
							'id' => $ticker->getId(),
							'fullname' => $ticker->getFullname(),
							'symbol' => $ticker->getSymbol(),
						], // For fullname and symbol
						'position' => $position->getId(),
						'currency' => [
							'symbol' => $currency->getSymbol(),
							'sign' => '$',
						], // For symbol
						'calendar' => [
							'exDiv' => $calendar->getExDividendDate(),
						], // For ex divdate
						'tax' => ['rate' => $tax->getTaxRate()], // For dividend tax
						'amount' => $adjustedAmount,
						'cashAmount' => $adjustedCashAmount,
						'exchangeRate' => $exchangeRates[$tickerId],
						'dividend' =>
							$adjustedCashAmount *
							$adjustedAmount *
							$exchangeRates[$tickerId] *
							(1-($taxRate / 100)),
					],
				],
			],
			'202511' => [
				// Payment date year and month
				'2025-11-03' => [
					// Payment date
					[
						'ticker' => [
							'id' => $ticker->getId(),
							'fullname' => $ticker->getFullname(),
							'symbol' => $ticker->getSymbol(),
						], // For fullname and symbol
						'position' => $position->getId(),
						'currency' => [
							'symbol' => $currency->getSymbol(),
							'sign' => '$',
						], // For symbol
						'calendar' => [
							'exDiv' => $calendar2->getExDividendDate(),
						], // For ex divdate
						'tax' => ['rate' => $tax->getTaxRate()], // For dividend tax
						'amount' => $adjustedAmount,
						'cashAmount' => $originalCashAmount2,
						'exchangeRate' => $exchangeRates[$tickerId],
						'dividend' =>
							$originalCashAmount2 *
							$adjustedAmount *
							$exchangeRates[$tickerId] *
							(1-($taxRate / 100)),
					],
				],
			],
		];

		$dividendCalendarService->load(
			tickers: $tickers,
			calendars: $calendars,
			transactions: $transactions,
			positions: $positions,
			exchangeRates: $exchangeRates
		);

		// Unit test result
		$actual = $dividendCalendarService->generate();

		$this->assertEquals($expected, $actual);
	}
}
