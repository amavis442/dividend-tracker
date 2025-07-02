<?php

namespace App\Service;

use App\Entity\Ticker;

class DividendGrowthService
{
    public function getData(Ticker $ticker)
    {
        $calendars = $ticker->getCalendars();
        $data = [];
        $cashPayouts = [];
        $labels = [];
        $payout = [];
        $values = [];

        if (count($calendars) > 0) {
            $oldValue = 0.0;
            $payoutFreq = $ticker->getPayoutFrequency();

            foreach ($calendars as $calendar) {
                $dividendType = $calendar->getDividendType();
                if ($dividendType == 'Special' || $dividendType == 'Supplement') {
                    continue;
                }
                $year = $calendar->getPaymentDate()->format('Y');
                $month = $calendar->getPaymentDate()->format('m');
                $cashPayout = $calendar->getCashAmount();
                if (!isset($data[$year])) {
                    $data[$year] = [];
                    $data[$year]['dividend'] = 0.0;
                    $data[$year]['payoutfreq'] = $payoutFreq;
                    $data[$year]['payouts'] = 0;
                }
                $cashPayouts[$year.$month] = $cashPayout;
                $data[$year]['dividend'] += $cashPayout;
                $data[$year]['payouts'] += 1;
            }
            ksort($data);
            foreach ($data as $year => &$item) {
                $item['percentage'] = 0.0;
                if ($item['payouts'] !== $item['payoutfreq']) {
                    $item['dividend'] = ($item['dividend'] / $item['payouts']) * $item['payoutfreq'];
                }
                $cashPayout = $item['dividend'];
                if ($oldValue > 0) {
                    $percentage = (($cashPayout - $oldValue) / $oldValue) * 100;
                    $percentage = round($percentage, 2);
                    $item['percentage'] = $percentage;
                }
                $oldValue = $cashPayout;

                $labels[] = $year;
                $payout[] = round($item['dividend'], 3);
                $values[] = round($item['percentage'], 3);
            }
        }
        return [
            'data' => $values,
            'payout' => $payout,
            'labels' => $labels,
            'cashPayout'=> $cashPayouts,
        ];
    }
}
