<?php

declare(strict_types=1);

namespace App\Service\ExchangeRate;

use App\Contracts\ExchangeRate\DividendExchangeRateResolverInterface;
use App\Contracts\ExchangeRate\ExchangeRateInterface;
use App\Entity\Calendar;
use App\Entity\Ticker;
use App\Exception\ExchangeRateUnavailableException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientException;
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
        return $this->rateToEuro($ticker->getCurrency()->getSymbol(), $ticker->getSymbol());
    }

    private function rateToEuro(string $symbol, ?string $tickerSymbol): float
    {
        if ($symbol === self::BASE_CURRENCY) {
            return 1.0;
        }

        try {
            $rates = $this->exchangeRateService->getRates();
        } catch (HttpClientException | \RuntimeException $exception) {
            throw new ExchangeRateUnavailableException(
                $this->unavailableMessage($symbol, $tickerSymbol, []),
                true,
                $exception
            );
        }

        $rate = (float) ($rates[self::RATE_SYMBOLS[$symbol] ?? $symbol] ?? 0.0);

        if ($rate <= 0.0) {
            throw new ExchangeRateUnavailableException(
                $this->unavailableMessage($symbol, $tickerSymbol, $rates),
                $rates === []
            );
        }

        return 1 / $rate;
    }

    /**
     * @param array<string, float> $rates
     */
    private function unavailableMessage(string $symbol, ?string $tickerSymbol, array $rates): string
    {
        return $this->translator->trans(
            'tickerSymbol:: Exchange rate for [Symbol] is currently unavailable. Available are: jsonSymbol',
            [
                'tickerSymbol' => $tickerSymbol,
                'Symbol' => $symbol,
                'jsonSymbol' => json_encode(array_keys($rates)),
            ]
        );
    }
}
