<?php
namespace App\Service;

use App\Entity\Calendar;
use App\Entity\Constants;
use App\Entity\Position;

class DividendService
{
    protected $forwardNetDividend;
    protected $position;

    public function getExchangeAndTax(Calendar $calendar): array
    {
        $exchangeRate = 1;
        $dividendTax = 0.15;

        switch ($calendar->getCurrency()->getSymbol()) {
            case 'EUR':
                $exchangeRate = 1;
                $dividendTax = Constants::TAX / 100;
                break;
            case 'USD':
                $exchangeRate = Constants::EXCHANGE_USDEUR;
                $dividendTax = Constants::TAX / 100;
                break;
            case 'GB':
                $exchangeRate = Constants::EXCHANGE_GBXEUR * 100;
                $dividendTax = Constants::TAX_GB / 100;
                break;
        }

        return [$exchangeRate, $dividendTax];
    }

    public function getForwardNetDividend(Position $position): ?float
    {
        $cashAmount = 0.0;
        $forwardNetDividend = 0.0;

        $forwardNetDividend = 0.0;
        if ($position->getTicker()->getCalendars()) {
            $calendar = $position->getTicker()->getCalendars()->first();
            if ($calendar) {
                [$exchangeRate, $dividendTax] = $this->getExchangeAndTax($calendar);
                $cashAmount = $calendar->getCashamount();
                $forwardNetDividend = $position->getAmount() * $cashAmount * $exchangeRate * (1 - $dividendTax);
            }
        }
        $this->forwardNetDividend = $forwardNetDividend;

        $this->position = $position;

        return $forwardNetDividend;
    }

    public function getForwardNetDividendYield(Position $position): ?float
    {
        $netDividendYield = 0.0;
        $forwardNetDividend = $this->forwardNetDividend;
        if ($this->position !== $position) {
            $forwardNetDividend = $this->getForwardNetDividend($position);
        }

        if ($forwardNetDividend) {
            $dividendFrequency = 4;
            if ($position->getTicker()->getDividendMonths()) {
                $dividendFrequency = $position->getTicker()->getPayoutFrequency();
            }
            $totalNetDividend = $forwardNetDividend * $dividendFrequency;
            $allocation = $position->getAllocation();
            $netDividendYield = round(($totalNetDividend / $allocation) * 100, 2);
        }

        $this->netDividendYield = $netDividendYield;

        return $netDividendYield;
    }
}
