<?php

namespace App\Service\ExchangeRate;

use App\Contracts\Dividend\DividendTaxRateResolverInterface;
use App\Contracts\ExchangeRate\ExchangeAndTaxResolverInterface;
use App\Entity\Calendar;
use App\Entity\Ticker;
use App\Dto\ExchangeTaxDto;

class ExchangeAndTaxResolver implements ExchangeAndTaxResolverInterface
{
    public function __construct(
        private DividendExchangeRateResolver $dividendExchangeRateResolver,
        private DividendTaxRateResolverInterface $dividendTaxRateResolver,
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
        $dividendTax = $this->dividendTaxRateResolver->getTaxRateForCalendar($calendar);

        $exchangeRate = $this->dividendExchangeRateResolver->getRateForCalendar(
            $calendar
        );

        $currency = ($calendar->getCurrency() ?? $ticker->getCurrency())?->getSymbol() ?? 'USD';

        return new ExchangeTaxDto(
            exchangeRate: $exchangeRate,
            taxAmount: $dividendTax,
            currency: $currency
        );
    }
}
