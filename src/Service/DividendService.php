<?php
namespace App\Service;

use App\Entity\Calendar;
use App\Entity\Constants;
use App\Entity\Position;
use App\Model\ExchangeRateModel;

class DividendService
{
    protected $forwardNetDividend;
    protected $position;
    protected $exchangeRateModel;

    public function __construct(ExchangeRateModel $exchangeRateModel)
    {
        $this->exchangeRateModel = $exchangeRateModel;
    }

    public function getExchangeAndTax(Calendar $calendar): array
    {
        $exchangeRate = 1;
        $dividendTax = 0.15;
        $rates = $this->exchangeRateModel->getRates();

        switch ($calendar->getCurrency()->getSymbol()) {
            case 'EUR':
                $exchangeRate = 1;
                $dividendTax = Constants::TAX / 100;
                break;
            case 'USD':
                $exchangeRate = 1 / $rates['USD'];
                $dividendTax = Constants::TAX / 100;
                break;
            case 'GB':
                $exchangeRate = 1 / $rates['GBP'];
                $dividendTax = Constants::TAX_GB / 100;
                break;
            case 'CAD':
                $exchangeRate = 1 / $rates['CAD'];
                $dividendTax = Constants::TAX / 100;
                break;
            default:
                $exchangeRate = 1 / $rates['USD'];
                $dividendTax = Constants::TAX / 100;
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
