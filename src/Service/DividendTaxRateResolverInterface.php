<?php
namespace App\Service;

use App\Entity\Calendar;

interface DividendTaxRateResolverInterface
{
	/**
	 * Needs Calendar, Ticker, Tax Entity data
	 * $calendar->getTicker()->getTax()
	 */
	function getTaxRateForCalendar(Calendar $calendar): float;
}
