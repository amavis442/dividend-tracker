<?php

namespace App\Service;

use App\Repository\PaymentRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class Payouts
{
    public function payout(PaymentRepository $paymentRepository, UserInterface $user): array
    {
        $data = $paymentRepository->getDividendsPerInterval('Month',$user);
        $labels = [];
        $dates = array_keys($data);
        foreach ($dates as $date) {
            $labels[] = strftime('%b %Y', strtotime($date . '01'));
        }

        foreach ($data as $item) {
            $dividends[] = ($item['dividend'] / 1000);
            $accumulative[] = ($item['accumulative'] / 1000);
        }

        return [
            'data' => json_encode($data),
            'labels' => json_encode($labels),
            'dividends' => json_encode($dividends),
            'accumulative' => json_encode($accumulative),
        ];
    }
}
