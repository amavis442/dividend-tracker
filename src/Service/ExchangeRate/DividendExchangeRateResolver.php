<?php

namespace App\Service\ExchangeRate;

use Symfony\Contracts\Translation\TranslatorInterface;
use App\Service\ExchangeRate\ExchangeRateInterface;
use App\Entity\Calendar;

class DividendExchangeRateResolver implements DividendExchangeRateResolverInterface
{
    public function __construct(
        private ExchangeRateInterface $exchangeRateService,
        private TranslatorInterface $translator
    ) {}

    public function getRateForCalendar(Calendar $calendar): float
    {
        $rates = $this->exchangeRateService->getRates();
        $symbol = $calendar->getCurrency()->getSymbol();

        if (count($rates) < 1 && $symbol !== 'EUR' || !isset($rates[$symbol])) {
            $msg = $this->translator->trans('tickerSymbol:: Exchange rate for [Symbol] is currently unavailable. Available are: jsonSymbol', [
                'tickerSymbol' => $calendar->getTicker()->getSymbol(),
                'Symbol' => $symbol,
                'jsonSymbol' => json_encode($rates)
            ]);
            throw new \RuntimeException($msg);
        }

        return match ($symbol) {
            'EUR' => 1,
            'USD', 'GBP', 'CAD', 'CHF' => 1 / $rates[$symbol],
            default => 1 / $rates['USD']
        };
    }
}

