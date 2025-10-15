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

	/**
	 * Key is tickerId
	 *
	 * @return array<int|string, array{
	 *     position: \App\Entity\Position,
	 *     ticker: \App\Entity\Ticker,
	 *     calendar_used: \App\Entity\Calendar|null,
	 *     total_shares: float,
	 *     exchange_rate: float,
	 *     tax_rate: float,
	 *     currency: string|null,
	 *     invested: float,
	 *     yield: array{
	 *         percentage: array{
	 *             gross: float,
	 *             net: float
	 *         },
	 *         cash: array{
	 *             gross: float,
	 *             net: float
	 *         }
	 *     },
	 *     cash: array{
	 *         per_month_per_share: array{
	 *             gross: float,
	 *             net: float
	 *         },
	 *         per_month_all_shares: array{
	 *             gross: float,
	 *             net: float
	 *         }
	 *     }
	 * }>
	 */
	public function process(): array
	{
		$data = [];
		foreach ($this->tickers as $tickerId => $ticker) {
			if (!isset($this->positions[$tickerId])) {
				continue;
			}
			$position = $this->positions[$tickerId];
			if ($position->getAllocation() <= 0) {
				continue;
			}

			$cashAmount = 0.0;
			$calendar = null;

			if (
				isset($this->calendars[$tickerId]) &&
				count($this->calendars[$tickerId]) > 0
			) {
				$calId = array_key_last($this->calendars[$tickerId]);
				$calendar = $this->calendars[$tickerId][$calId];
				$cashAmount = $calendar->getAdjustedCashAmount();
			}

			$exchangeRate = $this->exchangeRates[$tickerId];
			$invested = $position->getAllocation();

			$freq = $ticker->getDividendFrequency(); // Normalize to month
			$cashAmount = $cashAmount * ($freq/12); // Normalize to month


			$amount = $position->getAdjustedAmount();
			$tax = 1 - $ticker->getTax()->getTaxRate();

			$yieldPercentageGross =
				((12 * $cashAmount * $amount) / $invested) * 100;
			$yieldPercentageNet =
				((12 * $cashAmount * $amount * $tax * $exchangeRate) /
					($invested * $exchangeRate)) *
				100;

			$yieldCashGross = 12 * $cashAmount * $amount;

			$yieldCashNet =
				12 * $cashAmount * $amount * $tax * $exchangeRate;

			$data[$ticker->getId()] = [
				'position' => $position,
				'ticker' => $ticker,
				'calendar_used' => $calendar, // Use the lastest
				'total_shares' => $position->getAdjustedAmount(),
				'exchange_rate' => $exchangeRate,
				'tax_rate' => $ticker->getTax()->getTaxRate(),
				'currency' => $ticker->getCurrency()->getSymbol(),
				'invested' => $invested,
				'yield' => [
					'percentage' => [
						'gross' => $yieldPercentageGross,
						'net' => $yieldPercentageNet,
					],
					'cash' => [
						'gross' => $yieldCashGross,
						'net' => $yieldCashNet,
					],
				],
				'cash' => [
					'per_month_per_share' => [
						'gross' => $cashAmount,
						'net' => $exchangeRate * $cashAmount * $tax,
					],
					'per_month_all_shares' => [
						'gross' => $amount * $cashAmount,
						'net' => $cashAmount * $amount * $tax * $exchangeRate,
					],
				],
			];
		}

		return $data;
	}
}
