<?php

namespace App\Decorator;

use App\Entity\Calendar;
use App\Repository\PaymentRepository; // Has to go and data should be inserted as parameter
use Doctrine\Common\Collections\Collection;


final class InstrumentDecorator
{
    public function __construct(
        private readonly PaymentRepository $paymentRepo,
    ) {}

    /**
     * Adds dividend, currentDividend and avgDividend to each ticker entry.
     */
    public function dividend(
        Collection $dividends,
        array &$tickers,
        float $rateDollarEuro
    ): void {
        // Copy the original foreach that filled `$tickers[$id]['dividend']`
        // Use $corporateActions as needed. All the logic stays here.

        $lastYear = sprintf(
            '%04d-%02d-%02d',
            date('Y') - 1,
            date('m'),
            date('d')
        );

        $lastYear = (new \Datetime('-1 years -1 months'))->format('Ym');
        $tickerCalendars = [];
        foreach (array_keys($tickers) as $tickerId) {
            if (empty($dividends[$tickerId])) {
                continue;
            }
            $dividendCalendarData = $dividends[$tickerId];
            /**
             * @var Calendar $dividendCalendar
             */
            foreach ($dividendCalendarData as $dividendCalendar) {
                if ($dividendCalendar->getPaymentDate()->format('Ym') < $lastYear) {
                    continue;
                }
                $tickerCalendars[$dividendCalendar->getId()] = $dividendCalendar;
            }
        }

        foreach ($tickerCalendars as $calId => $tickerCalendar) {
            $tickerId = $tickerCalendar->getTicker()->getId();
            $frequency = $tickerCalendar->getTicker()->getPayoutFrequency();
            $cId = (int) $tickerCalendar->getPaymentDate()->format('Ym');

            //$cashAmount = $tickerCalendar
            $adjustedCalendar = $tickers[$tickerId]['adjustedDividend'][$calId] ?? ['adjusted' => $tickerCalendar->getCashAmount()];
            /*if (
				$tickerId == 291 &&
				$tickerCalendar->getPaymentDate()->format('Y-m-d') >
				'2025-10-01'
			) {
				dd($tickers[291]['adjustedDividend'], $calId, $tickerCalendar);
			}*/
            $cashAmount = $adjustedCalendar['adjusted'];

            $tickerCalendar->setAdjustedCashAmount($cashAmount);

            $tickers[$tickerId]['calendars'][$cId] = $tickerCalendar;

            if (!isset($tickers[$tickerId]['dividend'])) {
                $tickers[$tickerId]['dividend']['sumDividend'] = 0.0;
                $tickers[$tickerId]['dividend']['records'] = 0;
                $tickers[$tickerId]['dividend']['avg'] = 0.0;
                $tickers[$tickerId]['dividend']['predicted_payment'] = [];
                $tickers[$tickerId]['dividend']['predicted_payment_monthly'] = [];

                $tickers[$tickerId]['dividend']['frequency'] = $frequency;
            }

            if ($cId > $lastYear) {
                /*
				$tickers[$id]['dividend'][
					$cId
				] = $tickerCalendar->getCashAmount();
				*/
                $tickers[$tickerId]['dividend'][$cId] = $cashAmount;

                $tickers[$tickerId]['dividend']['sumDividend'] += $cashAmount;
                $tickers[$tickerId]['dividend']['records'] += 1;

                $owned = $tickers[$tickerId]['instrument']->getOwnedQuantity();
                $tax = $tickers[$tickerId]['tax']->getTax()->getTaxRate();

                $normalizeToMonthlyPaymentMultiplier = $frequency / 12;

                $predictedPayment =
                    $owned * $cashAmount * $rateDollarEuro * (1 - $tax);
                $tickers[$tickerId]['dividend']['predicted_payment'][$cId] = $predictedPayment;
                $tickers[$tickerId]['dividend']['predicted_payment_monthly'][$cId] = $predictedPayment * $normalizeToMonthlyPaymentMultiplier;
            }
        }
    }

    /**
     * Returns an array with keys:
     * - pieDividend
     * - pieCurrentDividend
     * - pieAvgDividend
     */
    public function instruments(
        float $pieAvgInvested,
        Collection $instruments,
        Collection $payments,
        array &$tickers,
        float $rateDollarEuro
    ): array {
        // The original `$this->decorateInstruments(...)` logic.
        $pieDividend = 0.0; // What is actually paid will be a computed on latest paydat so can be inaccurate. Trading212 does not split up payments by pie instruments :(
        $pieCurrentDividend = 0.0;
        $pieAvgDividend = 0.0;

        /**
         * @var \App\Entity\Trading212PieInstrument $instrument
         */
        foreach ($instruments as $instrument) {
            $ticker = $instrument->getTicker();
            if (!$ticker) {
                continue;
            }
            $tickerId = $instrument->getTicker()->getId();

            if ($instrument->getPriceAvgInvestedValue() == 0) {
                continue;
            }
            $instrumentTicker = $tickers[$tickerId];

            if (isset($instrumentTicker['dividend']['avg']) && $instrumentTicker['dividend']['records']) {
                $instrumentTicker['dividend']['avg'] =
                    $instrumentTicker['dividend']['sumDividend'] /
                    $instrumentTicker['dividend']['records'];
            } else {
                $instrumentTicker['dividend']['avg'] = 0.0;
            }

            if (isset($instrumentTicker['calendars'])) {
                //dd(array_slice($instrumentTicker['dividend'], -6, null, true) );
                //array_slice($instrumentTicker['calendars'], -6);
                $cals = array_slice(
                    $instrumentTicker['calendars'],
                    -6,
                    null,
                    true
                );
                ksort($cals);
                $instrument->setCalendars($cals); // Last 6 months if data is available
                $instrument->setDividend($instrumentTicker['dividend']);
            }
            $tax = $instrumentTicker['tax']->getTax()->getTaxRate();
            $instrument->setTaxRate($tax);
            $instrument->setExchangeRate($rateDollarEuro);

            $owned = $instrument->getOwnedQuantity();
            // Current
            $yearMonth = (int) date('Ym');
            $currentDividend = 0.0;
            if (isset($instrumentTicker['calendars'][$yearMonth])) {
                $currentDividend = $instrumentTicker['calendars'][$yearMonth]->getAdjustedCashAmount();
            }
            $instrument->setCurrentDividendPerShare($currentDividend);

            $totalCurrentDividend =
                $currentDividend * $owned * (1 - $tax) * $rateDollarEuro;
            $instrument->setCurrentDividend($totalCurrentDividend);

            $instrument->setMonthlyYield(0.0);
            if ($instrument->getPriceAvgInvestedValue() > 0) {
                $monthlyYield =
                    ($totalCurrentDividend /
                        $instrument->getPriceAvgInvestedValue()) *
                    100;
                $instrument->setMonthlyYield($monthlyYield);
            }

            $currentYearlYield =
                (($ticker->getPayoutFrequency() * $totalCurrentDividend) /
                    $instrument->getPriceAvgInvestedValue()) *
                100;
            $instrument->setCurrentYearlyYield($currentYearlYield);
            $pieCurrentDividend += $totalCurrentDividend;

            // Avg
            //$avgDividend = $calendarRepository->getAvgDividend($ticker);
            $avgDividend = $instrumentTicker['dividend']['avg'];
            $instrument->setAvgDividendPerShare($avgDividend);

            $avgExpectedDividend =
                $avgDividend * $owned * (1 - $tax) * $rateDollarEuro;
            $instrument->setAvgExpectedDividend($avgExpectedDividend);

            $avgYearlYield =
                (($ticker->getPayoutFrequency() * $avgExpectedDividend) /
                    $instrument->getPriceAvgInvestedValue()) *
                100;
            $instrument->setAvgYearlyYield($avgYearlYield);
            $pieAvgDividend += $avgExpectedDividend;

            $pieShare = round(
                ($instrument->getPriceAvgInvestedValue() / $pieAvgInvested) *
                    100,
                2
            );
            $pieInstruments['labels'][] = $ticker->getFullname();
            $pieInstruments['data'][] = $pieShare;


            /**
             * @var \App\Entity\Payment $payment
             */
            $payment = $this->paymentRepo->getLastDividend(
                $ticker,
                $instrument->getCreatedAt()
            );
            // @todo: Must come from $payments Collection <$tickerId, array<

            if ($payment) {
                $amount = $payment->getAmount();
                $dividend = $payment->getDividend();
                $instrumentDividendPaid = ($dividend / $amount) * $owned;
                $instrument->setDividendPaid($instrumentDividendPaid);
                $pieDividend += $instrumentDividendPaid;
            }
        }

        return [
            'pieDividend' => $pieDividend,
            'pieCurrentDividend' => $pieCurrentDividend,
            'pieAvgDividend' => $pieAvgDividend,
        ];
    }
}
