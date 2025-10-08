<?php

namespace App\Decorator;

use App\Entity\Ticker;

interface AdjustedDividendDecoratorInterface
{
	/**
	 * Returns adjusted dividend data keyed by \App\Entity\Calendar::Id()
	 *
	 * @return array<int, array{
	 * 	original: int,
	 * 	adjusted: float,
	 *  declareDate: \DateTime,
	 *  paymentDate: \DateTime,
	 *  ticker: Ticker,
	 *  symbol: string
	 * }>
	 */
	public function getAdjustedDividend(): array;

	/**
	 * Returns adjusted dividend data keyed by \App\Entity\Calendar::Id()
	 *
	 * @return array<int, array{
	 * 	original: int,
	 * 	adjusted: float,
	 *  declareDate: \DateTime,
	 *  paymentDate: \DateTime,
	 *  ticker: Ticker,
	 *  symbol: string
	 * }>
	 */
	public function getAdjustedDividendSortByPaymentDate(): array;
}
