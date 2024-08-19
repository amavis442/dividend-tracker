<?php

namespace App\Service;

use App\Entity\Ticker;

class DividendGrowthService
{
    public function getData(Ticker $ticker)
    {
        $calendars = $ticker->getCalendars();
        $data = [];
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
                $timeStamp = $calendar->getPaymentDate()->format('Y');
                $cashPayout = $calendar->getCashAmount();
                if (!isset($data[$timeStamp])) {
                    $data[$timeStamp] = [];
                    $data[$timeStamp]['dividend'] = 0.0;
                    $data[$timeStamp]['payoutfreq'] = $payoutFreq;
                    $data[$timeStamp]['payouts'] = 0;
                }
                $data[$timeStamp]['dividend'] += $cashPayout;
                $data[$timeStamp]['payouts'] += 1;
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
        ];
    }
}
