<?php

declare(strict_types=1);

namespace App\Service\ExchangeRate;

use App\Contracts\ExchangeRate\DividendExchangeRateResolverInterface;
use App\Contracts\ExchangeRate\ExchangeRateInterface;
use App\Entity\Calendar;
use App\Entity\Ticker;
use App\Exception\ExchangeRateUnavailableException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Resolves the factor that converts an amount in a given currency to euro.
 */
class DividendExchangeRateResolver implements DividendExchangeRateResolverInterface
{
    private const BASE_CURRENCY = 'EUR';

    /** Currency symbols in the database that are quoted under another symbol. */
    private const RATE_SYMBOLS = ['GB' => 'GBP'];

    public function __construct(
        private ExchangeRateInterface $exchangeRateService,
        private TranslatorInterface $translator
    ) {
    }

    public function getRateForCalendar(Calendar $calendar): float
    {
        return $this->rateToEuro($calendar->getCurrency()->getSymbol(), $calendar->getTicker()->getSymbol());
    }

    public function getRateForTicker(Ticker $ticker): float
    {
        $symbol = $ticker->getCurrency()->getSymbol();

        return $this->rateToEuro($symbol, $symbol);
    }

    private function rateToEuro(string $symbol, ?string $tickerSymbol): float
    {
        if ($symbol === self::BASE_CURRENCY) {
            return 1.0;
        }

        $rates = $this->exchangeRateService->getRates();
        $rate = (float) ($rates[self::RATE_SYMBOLS[$symbol] ?? $symbol] ?? 0.0);

        if ($rate <= 0.0) {
            throw new ExchangeRateUnavailableException($this->translator->trans(
                'tickerSymbol:: Exchange rate for [Symbol] is currently unavailable. Available are: jsonSymbol',
                [
                    'tickerSymbol' => $tickerSymbol,
                    'Symbol' => $symbol,
                    'jsonSymbol' => json_encode(array_keys($rates)),
                ]
            ));
        }

        return 1 / $rate;
    }
}
