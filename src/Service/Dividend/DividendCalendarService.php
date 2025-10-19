<?php

namespace App\Service\Dividend;

use App\Service\Transaction\ShareEligibilityCalculatorInterface;

final class DividendCalendarService
{
	/**
	 * @var array<int, array<int, \App\Entity\Transaction>> $transactions
	 */
	private array $transactions;
	/**
	 * @var array<int, array<int, \App\Entity\Calendar>> $calendars
	 */
	private array $calendars;
	/**
	 * @var array<int, \App\Entity\Ticker> $tickers
	 */
	private array $tickers;
	/**
	 * @var array<int, \App\Entity\Position> $position
	 */
	private array $positions;

	private ?array $exchangeRates;

	public function __construct(
		private ShareEligibilityCalculatorInterface $shareEligibilityCalculator
	) {
	}

	public function load(
		array $tickers,
		array $calendars,
		array $transactions,
		array $positions,
		?array $exchangeRates = null
	): void {
		$this->transactions = $transactions;
		$this->calendars = $calendars;
		$this->tickers = $tickers;
		$this->positions = $positions;
		$this->exchangeRates = $exchangeRates;
	}

	public function generate(): array
	{
		$data = [];

		foreach ($this->calendars as $tickerId => $calendarList) {
			$transactions = $this->transactions[$tickerId];
			$ticker = $this->tickers[$tickerId];
			$taxRate = $ticker->getTax()->getTaxRate();
			$currencySymbol = $ticker->getCurrency()->getSymbol();
			$currencySign = $ticker->getCurrency()->getSign();
			$position = $this->positions[$tickerId];
			$exchangeRate = $this->exchangeRates[$ticker->getId()] ?? 1.0;

			foreach ($calendarList as $calendar) {
				if (!isset($data[$calendar->getPaymentDate()->format('Ym')])) {
					$data[$calendar->getPaymentDate()->format('Ym')] = [];
				}

				$adjustedAmount = $this->shareEligibilityCalculator->calculate(
					transactions: $transactions,
					calendar: $calendar
				);

				$adjustedCashAmount = $calendar->getAdjustedCashAmount();

				$data[$calendar->getPaymentDate()->format('Ym')][
					$calendar->getPaymentDate()->format('Y-m-d')
				][] = [
					'ticker' => [
						'id' => $ticker->getId(),
						'fullname' => $ticker->getFullname(),
						'symbol' => $ticker->getSymbol(),
					],
					'position' => $position,
					'currency' => [
						'symbol' => $currencySymbol,
						'sign' => $currencySign,
					], // For symbol
					'calendar' => [
						'exDiv' => $calendar->getExDividendDate(),
					],
					'tax' => ['rate' => $taxRate], // For dividend tax
					'amount' => $adjustedAmount,
					'cashAmount' => $adjustedCashAmount,
					'exchangeRate' => $exchangeRate,
					'dividend' =>
						$adjustedCashAmount *
						$adjustedAmount *
						$exchangeRate *
						(1-$taxRate),
				];
			}
		}
		foreach ($data as $timestamp => &$records)
		{
			ksort($records);
		}
		ksort($data);

		return $data;
	}
}
