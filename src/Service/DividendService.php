<?php

namespace App\Service;

use App\Entity\Calendar;
use App\Entity\Constants;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Entity\Transaction;
use App\Repository\TaxRepository;
use App\Service\ExchangeRateService;
use Doctrine\Common\Collections\Collection;

class DividendService
{
    /**
     * Net dividend over the shares
     *
     * @var null|float
     */
    protected $forwardNetDividend;
    /**
     * Position
     *
     * @var Position
     */
    protected $position;
    /**
     * Current exchangerate
     *
     * @var ExchangeRateService
     */
    protected $exchangeRateService;
    /**
     * Dividend tax withhold
     *
     * @var TaxRepository
     */
    protected $taxRepository;
    /**
     * What is the net dividend per payout per share
     *
     * @var null|float
     */
    protected $netDividendPerShare;

    public function __construct(ExchangeRateService $exchangeRateService, TaxRepository $taxRepository)
    {
        $this->exchangeRateService = $exchangeRateService;
        $this->taxRepository = $taxRepository;
        $this->netDividendPerShare = null;
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
        $taxRate = 0;

        $ticker = $calendar->getTicker();
        $tax = $ticker->getTax();
        if ($tax) {
            $taxRate = $tax->getTaxRate();
            return $taxRate;
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
     * @param Position $position
     * @param Calendar $calendar
     * @return array
     */
    public function getExchangeAndTax(Position $position, Calendar $calendar): array
    {
        $exchangeRate = 1;
        $dividendTax = 0.15;
        $ticker = $position->getTicker();

        $dividendTax = $ticker->getTax() ? $ticker->getTax()->getTaxRate() : Constants::TAX / 100;
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

    /**
     * How many shares are applicable on ex dividenddate
     *
     * @param Calendar $calendar
     * @return float|null
     */
    public function getPositionAmount(Calendar $calendar): ?float
    {
        $amount = 0.0;
        $ticker = $calendar->getTicker();
        $position = $ticker->getPositions()->first();
        if ($position) {
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
    public function getNetDividend(Position $position, Calendar $calendar): ?float
    {
        $ticker = $position->getTicker();
        $dividendTax = $ticker->getTax() ? $ticker->getTax()->getTaxRate() : Constants::TAX / 100;
        $cashAmount = $this->getCashAmount($ticker);
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
                    $netDividend = $this->getNetDividend($position, $calendar);
                    $dividend = $amount * $netDividend;
                }
            }
        }

        return $dividend;
    }

    /**
     * Are there supplemental and/or special dividends being paid?
     * This will be gross and not net dividend. 
     *
     * @param Ticker $ticker
     * @return float|null
     */
    public function getCashAmount(Ticker $ticker): ?float 
    {
        $cashAmount = 0;
        $calendars = $ticker->getCalendars();
        if ($calendars) {
            /**
             * @var \App\Entity\Calendar $calendar
             */
            $calendar = $calendars->first();
            if ($calendar) {
                $cashAmount = $calendar->getCashamount();

                /**
                 * @var \App\Entity\Calendar $secondCalendar
                 */
                $secondCalendar = $calendars[1];
                if ($secondCalendar && $secondCalendar->getPaymentDate()->format('Ymd') === $calendar->getPaymentDate()->format('Ymd') && $secondCalendar->getDividendType() !== $calendar->getDividendType()) {
                    $cashAmount += $secondCalendar->getCashamount();
                }

            }
        }

        return $cashAmount;
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
        $ticker = $position->getTicker();
        $calendars = $ticker->getCalendars();
        if ($calendars) {
            /**
             * @var \App\Entity\Calendar $calendar
             */
            $calendar = $calendars->first();
            if ($calendar) {
                $cashAmount = $this->getCashAmount($ticker);
                $amount = $position->getAmount();
                $dividendTax = $ticker->getTax() ? $ticker->getTax()->getTaxRate() : Constants::TAX / 100;
                $exchangeRate = $this->getExchangeRate($calendar);
                $this->netDividendPerShare = $cashAmount * $exchangeRate * (1 - $dividendTax);
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

    /**
     * Get what is the net dividend per payout per share
     *
     * @return  null|float
     */
    public function getNetDividendPerShare(?Position $position): ?float
    {
        if (!$this->netDividendPerShare && $position) {
            $this->getForwardNetDividend($position);
        }

        return $this->netDividendPerShare;
    }
}
