<?php

namespace App\Service;

use App\DataProvider\CorporateActionDataProvider;
use App\DataProvider\DividendDataProvider;
use App\DataProvider\TransactionDataProvider;
use App\Entity\Calendar;
use App\Repository\PaymentRepository;
use App\Repository\DividendTrackerRepository;
use App\Repository\DividendCalendarRepository;
use App\Contracts\Dividend\DividendServiceInterface;

class Payouts
{
    private const MIN_POSITION_AMOUNT = 0.001;
    private const MIN_DIVIDEND = 0.01;

    public function __construct(
        private readonly TransactionDataProvider $transactionDataProvider,
        private readonly CorporateActionDataProvider $corporateActionDataProvider,
        private readonly DividendDataProvider $dividendDataProvider
    ) {
    }

    public function payout(PaymentRepository $paymentRepository): array
    {
        $data = $paymentRepository->getDividendsPerInterval();
        $labels = [];
        $dates = array_keys($data);
        foreach ($dates as $date) {
            $labels[] = (new \DateTime($date . '01'))->format('Y M');
        }

        $dividends = [];
        $accumulative = [];
        foreach ($data as $item) {
            $dividends[] = $item['dividend'];
            $accumulative[] = $item['accumulative'];
        }

        return [
            'data' => $data,
            'labels' => $labels,
            'dividends' => $dividends,
            'accumulative' => $accumulative,
        ];
    }

    public function payments(
        PaymentRepository $paymentRepository,
        DividendTrackerRepository $dividendTrackerRepository,
        DividendServiceInterface $dividendService,
        DividendCalendarRepository $calendarRepository
    ): array {
        $paydate = (new \DateTime('first day of this month -1 years'))->format(
            'Y-m-d'
        );
        $data = $paymentRepository->getSumPayments($paydate);
        $alloctionData = $dividendTrackerRepository->getAllocationsPerMonth($paydate);
        $allocations = [];
        foreach ($alloctionData as $item) {
            $id = (int)sprintf(
                '%04d%02d',
                $item['periodYear'],
                $item['periodMonth']
            );
            $allocations[$id] = $item['allocation'];
        }

        //$startDate = (new \DateTime('first day of this month'))->format('Y-m-d');
        $endDate = (new \DateTime('last day of this month'))->format('Y-m-d');
        $foreCastDividend = $this->forecastPerMonth(
            $dividendService,
            $calendarRepository->findForForecast($paydate, $endDate)
        );

        $labels = [];
        $dates = [];
        $dividends = [];
        $foreCastYields = [];
        $yields = [];
        foreach ($data as $item) {
            $labels[] = sprintf(
                '%04d-%02d',
                $item['periodYear'],
                $item['periodMonth']
            );
            $date = (int)sprintf(
                '%04d%02d',
                $item['periodYear'],
                $item['periodMonth']
            );

            $dates[] = $date;
            $dividends[] = $item['dividend'];

            if (!empty($allocations[$date])) {
                $yields[$date] = (($item['dividend'] * 12) / $allocations[$date]) * 100;

                if (isset($foreCastDividend[$date])) {
                    $foreCastYields[$date] = (($foreCastDividend[$date] * 12) / $allocations[$date]) * 100;
                }
            }
        }

        $labelMonths = array_fill_keys($labels, 'filler');

        $date = date('Ym');
        if (!isset($labelMonths[date('Y-m')])) {
            $labels[] = date('Y-m');
            $yields[$date] = 0.0;
        }
        if (!empty($allocations[$date]) && isset($foreCastDividend[$date])) {
            $yields[$date] = (($foreCastDividend[$date] * 12) / $allocations[$date]) * 100;
        }

        $forecastPerLabel = array_map(
            static fn(string $label): ?float => $foreCastDividend[str_replace('-', '', $label)] ?? null,
            $labels
        );

        return [
            'labels' => $labels,
            'dividends' => $dividends,
            'trendline' => $this->trendValues($dividends),
            'yields' => $yields,
            'trendlineYield' => $this->trendValues($yields),
            'forecast' => $forecastPerLabel,
            'trendlineForeCastYield' => $this->trendValues($foreCastYields),
        ];
    }

    /**
     * Value of the linear trend at every data point, never below zero.
     *
     * @param array<int|string, float|int|string> $values
     *
     * @return list<float>
     */
    private function trendValues(array $values): array
    {
        if ($values === []) {
            return [];
        }

        $values = array_values($values);
        $positions = array_keys($values);
        $trend = $this->trendline($positions, $values);

        return array_map(
            static fn(int $position): float => max(0.0, $trend['slope'] * $position + $trend['intercept']),
            $positions
        );
    }

    private function trendline(array $x, array $y): array
    {
        $n = count($x); // number of items in the array
        $x_sum = array_sum($x); // sum of all X values
        $y_sum = array_sum($y); // sum of all Y values

        $xx_sum = 0;
        $xy_sum = 0;

        for ($i = 0; $i < $n; $i++) {
            $xy_sum += $x[$i] * $y[$i];
            $xx_sum += $x[$i] * $x[$i];
        }

        // A single data point has no slope
        $denominator = $n * $xx_sum - $x_sum * $x_sum;
        $slope = $denominator == 0 ? 0.0 : ($n * $xy_sum - $x_sum * $y_sum) / $denominator;

        // calculate intercept
        $intercept = ($y_sum - $slope * $x_sum) / $n;

        return [
            'slope' => $slope,
            'intercept' => $intercept,
        ];
    }

    /**
     * Net forecast dividend per payment month (key: Ym), skipping calendars
     * without shares or with a negligible payout.
     *
     * @param Calendar[] $calendars
     *
     * @return array<string, float>
     */
    private function forecastPerMonth(DividendServiceInterface $dividendService, array $calendars): array
    {
        $this->loadForecastData($dividendService, $calendars);
        $dividendService->setAccumulateDividendAmount(false);

        $totalDividend = [];
        foreach ($calendars as $calendar) {
            if ($dividendService->getPositionAmount($calendar) < self::MIN_POSITION_AMOUNT) {
                continue;
            }

            $positionDividend = $dividendService->getTotalNetDividend($calendar);
            if ($positionDividend < self::MIN_DIVIDEND) {
                continue;
            }

            $month = $calendar->getPaymentDate()->format('Ym');
            $totalDividend[$month] = ($totalDividend[$month] ?? 0.0) + $positionDividend;
        }

        return $totalDividend;
    }

    /**
     * @param Calendar[] $calendars
     */
    private function loadForecastData(DividendServiceInterface $dividendService, array $calendars): void
    {
        $positions = [];
        $tickers = [];
        foreach ($calendars as $calendar) {
            $ticker = $calendar->getTicker();
            $position = $ticker->getPositions()->first();
            if (!$position) {
                continue;
            }

            $positions[$position->getId()] = $position;
            $tickers[$ticker->getId()] = $ticker;
        }

        $positions = array_values($positions);
        $tickers = array_values($tickers);

        $dividendService->load(
            transactions: $this->transactionDataProvider->load($positions),
            corporateActions: $this->corporateActionDataProvider->load($tickers),
            dividends: $this->dividendDataProvider->load(
                tickers: $tickers,
                types: [Calendar::REGULAR, Calendar::SUPPLEMENT, Calendar::SPECIAL]
            )
        );
    }
}
