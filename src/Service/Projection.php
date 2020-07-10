<?php

namespace App\Service;

use App\Repository\CalendarRepository;
use App\Repository\DividendMonthRepository;
use App\Entity\Payment;

class Projection
{
    public function projection(
        CalendarRepository $calendarRepository,
        DividendMonthRepository $dividendMonthRepository,
        float $taxDividend = 0.15,
        float $exchangeRate = 1.1
    ): array {
        $dividendEstimate = $calendarRepository->getDividendEstimate();
        $labels = [];
        $data = [];
        foreach ($dividendEstimate as $date => &$estimate) {
            $d = strftime('%B %Y', strtotime($date . '01'));
            $labels[] = $d;
            $payout = ($estimate['totaldividend'] * (1 - $taxDividend)) / $exchangeRate;
            $data[] = round($payout, 2);
            $estimate['payout'] = $payout;
            $estimate['normaldate'] = $d;
        }

        $dataSource = [];
        $d = $dividendMonthRepository->getAll();

        foreach ($d as $month => $dividendMonth) {
            $receivedDividendMonth = 0.0;
            $paydate = sprintf("%4d%02d", date('Y'), $month);
            $normalDate = strftime('%B %Y', strtotime($paydate . '01'));
            $dataSource[$paydate] = [];
            if (!isset($dividendEstimate[$paydate])) {
                $dataSource[$paydate]['totaldividend'] = 0;
                $dataSource[$paydate]['payout'] = 0;
                $dataSource[$paydate]['normaldate'] = $normalDate;
                $dataSource[$paydate]['tickers'] = [];
                foreach ($dividendMonth->getTickers() as $ticker) {
                    $dataSource[$paydate]['tickers'][$ticker->getTicker()] = [
                        'units' => 0.0,
                        'dividend' => 0.0,
                        'payout' => 0.0,
                        'payoutdate' => '',
                        'exdividend' => '',
                        'ticker' => $ticker,
                        'calendar' => null,
                        'payment' => null
                    ];
                }
            }
            if (isset($dividendEstimate[$paydate])) {
                $item = $dividendEstimate[$paydate];
                $dataSource[$paydate]['totaldividend'] = $item['totaldividend'];
                $dataSource[$paydate]['payout'] = $item['payout'];
                $dataSource[$paydate]['normaldate'] = $normalDate;
                $dataSource[$paydate]['tickers'] = [];
                foreach ($dividendMonth->getTickers() as $ticker) {
                    if (isset($item['tickers'][$ticker->getTicker()])) {
                        $tickerData = $item['tickers'][$ticker->getTicker()];
                        $dataSource[$paydate]['tickers'][$ticker->getTicker()] = $tickerData;
                        if ($tickerData['payment'] instanceof Payment) {
                            $receivedDividendMonth += $tickerData['payment']->getDividend();
                        }
                    }

                    if (!isset($item['tickers'][$ticker->getTicker()])) {
                        $dataSource[$paydate]['tickers'][$ticker->getTicker()] = [
                            'units' => 0,
                            'dividend' => 0,
                            'payout' => 0,
                            'payoutdate' => '',
                            'exdividend' => '',
                            'ticker' => $ticker,
                            'calendar' => null,
                            'payment' => null
                        ];
                    }
                }
            }
            $dataSource[$paydate]['received'] = $receivedDividendMonth / 100;
        }
        return [
            'data' => json_encode($data),
            'labels' => json_encode($labels),
            'datasource' => $dataSource,
        ];
    }
}
