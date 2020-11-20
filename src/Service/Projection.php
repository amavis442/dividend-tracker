<?php

namespace App\Service;

use App\Entity\DividendMonth;
use App\Repository\CalendarRepository;
use App\Repository\DividendMonthRepository;
use App\Repository\PositionRepository;

class Projection
{
    protected $taxDividend;
    protected $exchangeRate;

    private function calcEstimatePayoutPerMonth(array &$dividendEstimate)
    {
        foreach ($dividendEstimate as $date => &$estimate) {
            $d = strftime('%B %Y', strtotime($date . '01'));
            $labels[] = $d;
            //$estimatedNetTotalPayment = ($estimate['grossTotalPayment'] * (1 - $this->taxDividend)) / $this->exchangeRate;
            //$data[] = round($estimatedNetTotalPayment, 2);
            //$estimate['estimatedNetTotalPayment'] = round($estimatedNetTotalPayment, 2);
            $estimate['normaldate'] = $d;
        }
    }

    private function initEmptyDatasourceItem(
        array &$dataSource,
        DividendMonth &$dividendMonth,
        string $paydate,
        string $normalDate
    ) {
        $dataSource[$paydate]['grossTotalPayment'] = 0.0;
        $dataSource[$paydate]['estimatedNetTotalPayment'] = 0.0;
        $dataSource[$paydate]['normaldate'] = $normalDate;
        $dataSource[$paydate]['tickers'] = [];
        foreach ($dividendMonth->getTickers() as $ticker) {
            $dataSource[$paydate]['tickers'][$ticker->getTicker()] = [
                'amount' => 0.0,
                'dividend' => 0.0,
                'payoutdate' => '',
                'exdividend' => '',
                'ticker' => $ticker,
                'calendar' => null,
                'position' => null,
                'netPayment' => 0.0,
                'estimatedPayment' => 0.0,
            ];
        }
    }

    private function fillDataSourceItem(
        array &$dataSource,
        DividendMonth &$dividendMonth,
        float &$receivedDividendMonth,
        array $dividendEstimate,
        string $paydate,
        string $normalDate,
        array &$data,
        array &$labels
    ) {
        $item = $dividendEstimate[$paydate];
        $dataSource[$paydate]['grossTotalPayment'] = $item['grossTotalPayment'];
        $dataSource[$paydate]['estimatedNetTotalPayment'] = 0.0;
        $dataSource[$paydate]['normaldate'] = $normalDate;
        $dataSource[$paydate]['tickers'] = [];
        foreach ($dividendMonth->getTickers() as $ticker) {
            if (isset($item['tickers'][$ticker->getTicker()])) {
                $tickerData = $item['tickers'][$ticker->getTicker()];
                $dataSource[$paydate]['tickers'][$ticker->getTicker()] = $tickerData;
                $receivedDividendMonth += $tickerData['netPayment'];

                $units = $dataSource[$paydate]['tickers'][$ticker->getTicker()]['amount'];
                $dividend = $dataSource[$paydate]['tickers'][$ticker->getTicker()]['dividend'];
                $estimatedPayment = ($units * $dividend * (1 - $this->taxDividend)) / $this->exchangeRate;
                $dataSource[$paydate]['tickers'][$ticker->getTicker()]['estimatedPayment'] = round($estimatedPayment, 2);

                $dataSource[$paydate]['estimatedNetTotalPayment'] += round($estimatedPayment, 2);
            }

            if (!isset($item['tickers'][$ticker->getTicker()])) {
                $dataSource[$paydate]['tickers'][$ticker->getTicker()] = [
                    'amount' => 0.0,
                    'dividend' => 0.0,
                    'payoutdate' => '',
                    'exdividend' => '',
                    'ticker' => $ticker,
                    'calendar' => null,
                    'position' => null,
                    'netPayment' => 0.0,
                    'estimatedPayment' => 0.0,
                ];
            }
        }
        $data[] = round($dataSource[$paydate]['estimatedNetTotalPayment'], 2);
        $labels[] = $normalDate;
    }

    public function projection(
        ?int $year = null,
        PositionRepository $positionRepository,
        CalendarRepository $calendarRepository,
        DividendMonthRepository $dividendMonthRepository,
        float $taxDividend = 0.15,
        float $exchangeRate = 1.19
    ): array{
        $labels = [];
        $data = [];
        $dividendEstimate = [];

        $this->exchangeRate = $exchangeRate;
        $this->taxDividend = $taxDividend;

        $dividendEstimate = [];
        $positions = $positionRepository->getAllOpen();
        foreach ($positions as $position) {
            $positionDividendEstimate = $calendarRepository->getDividendEstimate($position, $year);
            foreach ($positionDividendEstimate as $payDate => $estimate) {
                if ($payDate) {
                    if (!isset($dividendEstimate[$payDate])) {
                        $dividendEstimate[$payDate] = [];
                        $dividendEstimate[$payDate]['tickers'] = [];
                        $dividendEstimate[$payDate]['grossTotalPayment'] = 0.0;
                    }
                    $tickers = array_keys($estimate['tickers']);
                    foreach ($tickers as $symbol) {
                        $dividendEstimate[$payDate]['tickers'][$symbol] = $estimate['tickers'][$symbol];
                        $amount = $estimate['tickers'][$symbol]['amount'];
                        $dividend = $estimate['tickers'][$symbol]['dividend'];
                        $dividendEstimate[$payDate]['grossTotalPayment'] += round($amount * $dividend, 2);
                    }
                }
            }
        }
        ksort($dividendEstimate);
        
        $this->calcEstimatePayoutPerMonth($dividendEstimate);

        $dataSource = [];
        $d = $dividendMonthRepository->getAll();

        foreach ($d as $month => $dividendMonth) {
            $receivedDividendMonth = 0.0;
            $paydate = sprintf("%4d%02d", $year, $month);
            $normalDate = strftime('%B %Y', strtotime($paydate . '01'));
            $dataSource[$paydate] = [];
            if (!isset($dividendEstimate[$paydate])) {
                $this->initEmptyDatasourceItem($dataSource, $dividendMonth, $paydate, $normalDate);
            }

            if (isset($dividendEstimate[$paydate])) {
                $this->fillDataSourceItem($dataSource, $dividendMonth, $receivedDividendMonth, $dividendEstimate, $paydate, $normalDate, $data, $labels);
            }
            $dataSource[$paydate]['netTotalPayment'] = $receivedDividendMonth;
        }

        return [
            'data' => json_encode($data),
            'labels' => json_encode($labels),
            'datasource' => $dataSource,
        ];
    }
}
