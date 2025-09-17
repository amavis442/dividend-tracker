<?php

namespace App\Service;

use App\Entity\Calendar;
use App\Entity\Position;
use App\Entity\Pie;
use App\Entity\Ticker;
use Doctrine\Common\Collections\Collection;

/**
 * This interface is for any dividendservice that
 * - calculates eligable shares for receiving dividend payment on a given date
 * - implements changes like reverse-split and split
 * 	- return adjusted amount
 *  - return adjusted declared dividend cash amount
 * Since we need to pay tax and are subject to exchange rates, there will be functions to calculate how much netto is to be expected per position
 */
interface DividendServiceInterface
{
	/**
	 * Get the exchange rate and tax rate
	 *
	 * @param Position $position
	 *
	 * @param Calendar $calendar
	 *
	 * @return array
	 *
	 * @deprecated Use ExchangeAndTaxResolver::class or ExchangeAndTaxResolverInterface
	 */
	public function getExchangeAndTax(
		Position $position,
		Calendar $calendar
	): array;

	/**
	 * This returns the position size for a current dividend payment. Shares bought before ex-div date are eligable. After they are not and will
	 * be ignored. This does not implement corporate actions
	 *
	 * @param Collection $transactions
	 *
	 * @param Calendar $calendar
	 *
	 * @deprecated Use DividendServiceInterface::getPositionAmount() or App\Service\ShareEligibilityCalculator::calculate(Collection $transactions, Calendar $calendar): float
	 */
	public function getPositionSize(
		Collection $transactions,
		Calendar $calendar
	): ?float;

	/**
	 * We have different types like Regular, Special, Extra dividend payments
	 * Since the Special and Extra are not regular and should not be used for dividend yield calculations
	 * It return the current calendar
	 *
	 * @param Ticker $ticker
	 *
	 * @return null|Calendar
	 */
	public function getRegularCalendar(Ticker $ticker): ?Calendar;

	/**
	 * Get the eligable amount of shares that get a payment.
	 * This also implments corporate actions
	 *
	 * @param Calendar $calendar
	 */
	public function getPositionAmount(Calendar $calendar): ?float;

	/**
	 * Return the number of shares in a position adjusted by corporate events like
	 * splits and reverse-splits
	 *
	 * @param Position $position
	 */
	public function getSharesPerPositionAmount(Position $position): ?float;

	/**
	 * Returns the expected netto dividend payment (exchange rate and taxes) for 1 share in a position
	 *
	 * @param Position $position
	 *
	 * @param Calendar $calendar
	 *
	 * @return null|float
	 */
	public function getNetDividend(
		Position $position,
		Calendar $calendar
	): ?float;

	/**
	 * Returns the expected netto dividend payment (exchange rate and taxes) for all the shares in a position
	 *
	 * @param Calendar $calendar
	 */
	public function getTotalNetDividend(Calendar $calendar): ?float;

	/**
	 * Returns the declared cashamount. Implement corporate actions.
	 *
	 *
	 * @param Ticker $ticker
	 *
	 * @return null|float
	 */
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

	public function setAccumulateDividendAmount(
		bool $accumulateDividendAmount = true
	): DividendServiceInterface;

	public function getCalendarDataPerMonth(
		int $year,
		?string $startDate = null,
		?string $endDate = null,
		?Pie $pie = null
	): array;
}
