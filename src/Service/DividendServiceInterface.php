<?php

namespace App\Service;

use App\Entity\Calendar;
use App\Entity\Position;
use App\Entity\Ticker;
use Doctrine\Common\Collections\Collection;

interface DividendServiceInterface
{
	/**
	 * @deprecated Use App\Service\ExchangeAndTaxResolverInterface
	 */
	public function getExchangeAndTax(
		Position $position,
		Calendar $calendar
	): array;
	/**
	 * @deprecated Use App\Service\ShareEligibilityCalculator::calculate(Collection $transactions, Calendar $calendar): float
	 */
	public function getPositionSize(
		Collection $transactions,
		Calendar $calendar
	): ?float;
	public function getRegularCalendar(Ticker $ticker): ?Calendar;
	public function getPositionAmount(Calendar $calendar): ?float;
	public function getNetDividend(
		Position $position,
		Calendar $calendar
	): ?float;
	public function getTotalNetDividend(Calendar $calendar): ?float;
	public function getCashAmount(Ticker $ticker): ?float;
	public function getForwardNetDividend(
		Ticker $ticker,
		float $amount
	): ?float;
	public function getForwardNetDividendYield(
		Position $position,
		Ticker $ticker,
		float $amount,
		float $allocation
	): ?float;
	public function getNetDividendPerShare(?Position $position): ?float;
	public function setCummulateDividendAmount(
		bool $cummulateDividendAmount = true
	): DividendServiceInterface;
}
