<?php

namespace App\Dto;

final class BasicDataSetDto
{
	/**
	 * @param array<int, \App\Entity\Ticker> $tickers
	 * @param array<int, array <int, \App\Entity\Calendar>> $calendars
	 * @param array<int, array <int, \App\Entity\Transaction>> $transactions
	 * @param array<int, \App\Entity\Position> $positions
	 * @param array<int, float> $exchangeRates
	 *
	 */
	public function __construct(
		public readonly array $tickers,
		public readonly array $calendars,
		public readonly array $transactions,
		public readonly array $positions,
		public readonly array $exchangeRates
	) {
	}
}
