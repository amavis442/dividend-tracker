<?php

namespace App\Service\Dividend;

class DividendYieldCalculator
{
	/**
	 * @var array<int, \App\Entity\Ticker>
	 */
	private array $tickers;

	/**
	 * @var array<int,array<int, \App\Entity\Calendar>>
	 */
	private array $calendars;

	/**
	 * @var array<int, \App\Entity\Position>
	 */

	private array $positions;
	/**
	 * @var array<int, float>
	 */
	private array $exchangeRates;

	public function load(
		array $tickers,
		array $calendars,
		array $positions,
		array $exchangeRates
	): void {
     	$this->tickers = $tickers;
		$this->calendars = $calendars;
		$this->positions = $positions;
    	$this->exchangeRates = $exchangeRates;

	}

	public function process(): array
	{
    	$data = [];
		foreach ($this->tickers as $tickerId => $ticker) {
            if (!isset($this->positions[$tickerId])){
                continue;
            }
			$position = $this->positions[$tickerId];
			$calendar = $this->calendars[$tickerId][count($this->calendars[$tickerId]) - 1];
			$exchangeRate = $this->exchangeRates[$tickerId];
            $invested = $position->getAllocation();
            $freq = $ticker->getDividendFrequency();
            $cashAmount = $calendar->getAdjustedCashAmount();
            $amount = $position->getAdjustedAmount();
            $tax = (1 - $ticker->getTax()->getTaxRate());

			$data[$ticker->getId()] = [
				'position' => $position,
				'ticker' => $ticker,
				'calendar_used' => $calendar, // Use the lastest
				'total_shares' => $position->getAdjustedAmount(),
				'exchange_rate' => $exchangeRate,
				'tax_rate' => $ticker->getTax()->getTaxRate(),
				'currency' => $ticker->getCurrency()->getSymbol(),
				'invested' =>
					$invested,
				'yield' => [
					'percentage' => [
						'gross' =>
							(($freq *
								$cashAmount *
								$amount) /
								$invested) *
							100,
						'net' =>
							(($freq *
								$cashAmount *
								$amount *
								$tax *
								$exchangeRate) /
								($invested * $exchangeRate)) *
							100,
					],
					'cash' => [
						'gross' =>
							$freq *
							$cashAmount *
							$amount,
						'net' =>
							$freq *
							$cashAmount *
							$amount *
							$tax *
							$exchangeRate,
					],
				],
				'cash' => [
					'per_month_per_share' => [
						'gross' => $cashAmount,
						'net' =>
							$exchangeRate *
							$cashAmount *
							$tax,
					],
					'per_month_all_shares' => [
						'gross' =>
							$amount *
							$cashAmount,
						'net' =>
							$cashAmount *
							$amount *
							$tax *
							$exchangeRate,
					],
				],
			];
		}

		return $data;
	}
}
