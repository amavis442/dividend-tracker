<?php

namespace App\Service;

use App\Contracts\Service\DividendServiceInterface;
use App\Entity\Calendar;
use App\Entity\Constants;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Entity\Transaction;
use App\Repository\TaxRepository;
use App\Service\ExchangeRate\EuExchangeRateService as ExchangeRateService;
use Doctrine\Common\Collections\Collection;
use Symfony\Contracts\Translation\TranslatorInterface;

class DividendService implements DividendServiceInterface
{
    /**
     * Net dividend over the shares
     *
     * @var null|float
     */
    protected null|float $forwardNetDividend;
    /**
     * Position
     *
     * @var Position
     */
    protected Position $position;
    /**
     * Current exchangerate
     *
     * @var ExchangeRateService
     */
    protected ExchangeRateService $exchangeRateService;
    /**
     * Dividend tax withhold
     *
     * @var TaxRepository
     */
    protected TaxRepository $taxRepository;
    /**
     * What is the net dividend per payout per share
     *
     * @var null|float
     */
    protected null|float $netDividendPerShare;

    /**
     * Should all dividend paid on same day to same ticker be accumulated?
     * Normal dividend + Supplement dividend, etc
     *
     * @var boolean
     */
    protected bool $cummulateDividendAmount = true;

    protected TranslatorInterface $translator;

    protected float $netDividendYield = 0;

    public function __construct(ExchangeRateService $exchangeRateService, TaxRepository $taxRepository, TranslatorInterface $translator)
    {
        $this->exchangeRateService = $exchangeRateService;
        $this->taxRepository = $taxRepository;
        $this->netDividendPerShare = null;
        $this->translator = $translator;
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
        if (count($rates) < 1 && $calendar->getCurrency()->getSymbol() != 'EUR' || !isset($rates[$calendar->getCurrency()->getSymbol()])) {
            $msg = $this->translator->trans('tickerSymbol:: Exchange rate for [Symbol] is currently unavailable. Available are: jsonSymbol', [
                'tickerSymbol' => $calendar->getTicker()->getSymbol(),
                'Symbol' => $calendar->getCurrency()->getSymbol(),
                'jsonSymbol' => json_encode($rates)
            ]);

            throw new \RuntimeException($msg);
        }

        $exchangeRate = match ($calendar->getCurrency()->getSymbol()) {
            'EUR' => 1,
            'USD' => 1 / $rates['USD'],
            'GB' => 1 / $rates['GBP'],
            'CAD' => 1 / $rates['CAD'],
            'CHF' => 1 / $rates['CHF'],
            default => 1 / $rates['USD']
        };

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
     * @return null|float
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
     * Get the first regular dividend calendar item. No special or suplement dividends.
     *
     * @param Ticker $ticker
     * @return null|Calendar
     */
    public function getRegularCalendar(Ticker $ticker): ?Calendar
    {
        if (!$ticker->hasCalendar()) {
            return null;
        }

        $calendars = $ticker->getCalendars()->slice(0, 8);
        $calendars = array_filter($calendars, function ($element) {
            return $element->getDividendType() === Calendar::REGULAR || $element->getDividendType() === null;
        });

        if (count($calendars) > 0) {
            reset($calendars);
            return current($calendars);
        }

        return null;
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
            $amount = $this->getPositionSize($position->getTransactions(), $calendar);
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
        $cashAmount = $calendar->getCashAmount();
        if ($this->cummulateDividendAmount) {
            $cashAmount = $this->getCashAmount($ticker);
        }
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
        if (count($positions) > 0) {
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
     *
     * @param Ticker $ticker
     * @return float|null
     */
    public function getCashAmount(Ticker $ticker): ?float
    {
        $cashAmount = 0;
        $calendars = $ticker->getCalendars();
        if (count($calendars) > 0) {
            /**
             * @var \App\Entity\Calendar $calendar
             */
            $calendar = $this->getRegularCalendar($ticker);
            if ($calendar) {
                $cashAmount = $calendar->getCashamount();
            }
        }

        return $cashAmount;
    }

    /**
     * Get the expected regular dividend for the next dividend payout date
     *
     * @param Position $position
     * @return float|null
     */
    public function getForwardNetDividend(Ticker $ticker, float $amount): ?float
    {
        $cashAmount = 0.0;
        $forwardNetDividend = 0.0;
        $calendars = $ticker->getCalendars();
        if (count($calendars) > 0) {
            /**
             * @var \App\Entity\Calendar $calendar
             */
            $calendar = $this->getRegularCalendar($ticker);
            if ($calendar) {
                $cashAmount = $calendar->getCashAmount();
                if ($this->cummulateDividendAmount) {
                    $cashAmount = $this->getCashAmount($ticker);
                }

                $dividendTax = $ticker->getTax() ? $ticker->getTax()->getTaxRate() : Constants::TAX / 100;
                $exchangeRate = $this->getExchangeRate($calendar);
                $this->netDividendPerShare = $cashAmount * $exchangeRate * (1 - $dividendTax);
                $forwardNetDividend = (float) $amount * $cashAmount * $exchangeRate * (1 - $dividendTax);
            }
        }
        $this->forwardNetDividend = $forwardNetDividend;

        return $forwardNetDividend;
    }

    /**
     * What will be the yield based on the last dividend payout
     *
     * @param Position $position
     * @return float|null
     */
    public function getForwardNetDividendYield(Position $position, Ticker $ticker, float $amount, float $allocation): ?float
    {
        if ($position->getClosed() == true) {
            return null;
        }

        $netDividendYield = 0.0;
        $forwardNetDividend = $this->getForwardNetDividend($position->getTicker(), $amount);

        if ($forwardNetDividend) {
            $dividendFrequency = 4;
            if ($position->getTicker()->getDividendMonths()) {
                $dividendFrequency = $position->getTicker()->getPayoutFrequency();
            }
            $totalNetDividend = $forwardNetDividend * $dividendFrequency;
            
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
            $this->getForwardNetDividend($position->getTicker(), $position->getAmount());
        }

        return $this->netDividendPerShare;
    }

    /**
     * Set normal dividend + Supplement dividend, etc
     * Normal dividend + Supplement dividend, etc
     *
     * @param  boolean  $cummulateDividendAmount
     *
     * @return  self
     */
    public function setCummulateDividendAmount(bool $cummulateDividendAmount = true): self
    {
        $this->cummulateDividendAmount = $cummulateDividendAmount;

        return $this;
    }
}
