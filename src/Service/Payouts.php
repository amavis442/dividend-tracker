<?php

namespace App\Service;

use App\Repository\PaymentRepository;

class Payouts
{
    public function payout(PaymentRepository $paymentRepository): array
    {
        $data = $paymentRepository->getDividendsPerInterval();
        $labels = [];
        $dates = array_keys($data);
        foreach ($dates as $date) {
            $labels[] = strftime('%b %Y', strtotime($date . '01'));
        }

        foreach ($data as $item) {
            $dividends[] = ($item['dividend'] / 100);
            $accumulative[] = ($item['accumulative'] / 100);
        }

        return [
            'data' => json_encode($data),
            'labels' => json_encode($labels),
            'dividends' => json_encode($dividends),
            'accumulative' => json_encode($accumulative),
        ];
    }
}
