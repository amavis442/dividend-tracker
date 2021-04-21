<?php
namespace App\Service;

use App\Entity\Calendar;
use App\Entity\Constants;
use App\Entity\Position;
use App\Entity\Transaction;
use App\Repository\TaxRepository;
use App\Service\ExchangeRateService;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

class DividendService
{
    protected $forwardNetDividend;
    protected $position;
    protected $exchangeRateService;
    protected $taxRepository;

    public function __construct(ExchangeRateService $exchangeRateService, TaxRepository $taxRepository)
    {
        $this->exchangeRateService = $exchangeRateService;
        $this->taxRepository = $taxRepository;
    }

    /**
     * Get the exchange rat for this calendar event
     *
     * @param Calendar $calendar
     * @return float|null
     */
    public function getExchangeRate(Calendar $calendar): ?float
    {
        $rates = $this->exchangeRateService->getRates();
        switch ($calendar->getCurrency()->getSymbol()) {
            case 'EUR':
                $exchangeRate = 1;
                break;
            case 'USD':
                $exchangeRate = 1 / $rates['USD'];
                break;
            case 'GB':
                $exchangeRate = 1 / $rates['GBP'];
                break;
            case 'CAD':
                $exchangeRate = 1 / $rates['CAD'];
                break;
            case 'CHF':
                $exchangeRate = 1 / $rates['CHF'];
                break;   
            default:
                $exchangeRate = 1 / $rates['USD'];
                break;
        }

        return $exchangeRate;
    }

    /**
     * WHat is the dividend tax
     *
     * @param Calendar $calendar
     * @return float|null
     */
    public function getTaxRate(Calendar $calendar): ?float
    {
        $dividendTax = 0.15;
        $taxRate = $this->taxRepository->findOneValid($calendar->getCurrency(), (new DateTime()));

        if ($taxRate) {
            return $taxRate->getTaxRate() / 100;
        }      
        switch ($calendar->getCurrency()->getSymbol()) {
            case 'EUR':
                $dividendTax = Constants::TAX / 100;
                break;
            case 'USD':
                $dividendTax = Constants::TAX / 100;
                break;
            case 'GB':
                $dividendTax = Constants::TAX_GB / 100;
                break;
            case 'CAD':
                $dividendTax = Constants::TAX / 100;
                break;
            default:
                $dividendTax = Constants::TAX / 100;
                break;
        }

        return $dividendTax;
    }

    /**
     * Get the exchange rate and tax rate
     *
     * @param Calendar $calendar
     * @return array
     */
    public function getExchangeAndTax(Calendar $calendar): array
    {
        $exchangeRate = 1;
        $dividendTax = 0.15;

        $dividendTax = $this->getTaxRate($calendar);
        $exchangeRate = $this->getExchangeRate($calendar);

        return [$exchangeRate, $dividendTax];
    }

    /**
     * Which amount of shares should be considered for the dividend on a certain date
     *
     * @param Collection $transactions
     * @param Calendar $calendar
     * @return void
     */
    public function getPositionSize(Collection $transactions, Calendar $calendar): ?float
    {
        $shares = 0.0;

        foreach ($transactions as $transaction) {
            if ($transaction->getTransactionDate() >= $calendar->getExdividendDate()) {
                continue;
            }
            $amount = $transaction->getAmount();
            if ($transaction->getSide() === Transaction::BUY) {
                $shares += $amount;
            }
            if ($transaction->getSide() === Transaction::SELL) {
                $shares -= $amount;
            }
        }

        return $shares;
    }

    public function getPositionAmount(Calendar $calendar): ?float
    {
        $amount = 0.0;
        $ticker = $calendar->getTicker();
        $positions = $ticker->getPositions();
        if ($positions) {
            $position = $positions->first();
            if ($position) {
                $amount = $this->getPositionSize($position->getTransactions(), $calendar);
            }
        }
        return $amount > 0 ? $amount : 0.0;
    }

    /**
     * Get the net dividend payout
     *
     * @param Calendar $calendar
     * @return float|null
     */
    public function getNetDividend(Calendar $calendar): ?float
    {
        $cashAmount = $calendar->getCashAmount();
        $dividendTax = $this->getTaxRate($calendar);
        $exchangeRate = $this->getExchangeRate($calendar);

        return $cashAmount * (1 - $dividendTax) * $exchangeRate;
    }

    /**
     * Get total net dividend on calender ex div date
     *
     * @param Calendar $calendar
     * @return float|null
     */
    public function getTotalNetDividend(Calendar $calendar): ?float
    {
        $dividend = 0.0;

        $ticker = $calendar->getTicker();
        $positions = $ticker->getPositions();
        if ($positions) {
            $position = $positions->first();
            if ($position) {
                $amount = $this->getPositionSize($position->getTransactions(), $calendar);
                if ($amount > 0) {
                    $netDividend = $this->getNetDividend($calendar);
                    $dividend = $amount * $netDividend;
                }
            }
        }

        return $dividend;
    }

    /**
     * Get the expected dividend for the next dividend payout date
     *
     * @param Position $position
     * @return float|null
     */
    public function getForwardNetDividend(Position $position): ?float
    {
        $cashAmount = 0.0;
        $forwardNetDividend = 0.0;
        if ($position->getTicker()->getCalendars()) {
            $calendar = $position->getTicker()->getCalendars()->first();
            if ($calendar) {
                [$exchangeRate, $dividendTax] = $this->getExchangeAndTax($calendar);
                $cashAmount = $calendar->getCashamount();
                $amount = $position->getAmount();
                $forwardNetDividend = $amount * $cashAmount * $exchangeRate * (1 - $dividendTax);
            }
        }
        $this->forwardNetDividend = $forwardNetDividend;

        $this->position = $position;

        return $forwardNetDividend;
    }

    /**
     * What will be the yield based on the last dividend payout
     *
     * @param Position $position
     * @return float|null
     */
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
