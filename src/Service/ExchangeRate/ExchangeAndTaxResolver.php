<?php

namespace App\Service\ExchangeRate;

use App\Entity\Calendar;
use App\Entity\Ticker;
use App\Util\Constants;
use App\Dto\ExchangeTaxDto;

class ExchangeAndTaxResolver implements ExchangeAndTaxResolverInterface
{
	public function __construct(
		private DividendExchangeRateResolver $dividendExchangeRateResolver
	) {
	}

	/**
	 * Resolves the exchange rate and tax amount for a given ticker and calendar context.
	 *
	 * @param Ticker $ticker       The financial instrument to evaluate.
	 * @param Calendar $calendar   The date and currency context for the resolution.
	 *
	 * @return ExchangeTaxDto      DTO containing exchange rate, tax amount, and currency symbol.
	 */
	public function resolve(Ticker $ticker, Calendar $calendar): ExchangeTaxDto
	{
		$dividendTax = $ticker->getTax()
			? $ticker->getTax()->getTaxRate()
			: Constants::TAX / 100;

		$exchangeRate = $this->dividendExchangeRateResolver->getRateForCalendar(
			$calendar
		);

		$currency = $calendar->getCurrency()->getSymbol();

		return new ExchangeTaxDto(
			exchangeRate: $exchangeRate,
			taxAmount: $dividendTax,
			currency: $currency
		);
	}
}
