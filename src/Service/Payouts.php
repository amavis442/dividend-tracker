<?php

namespace App\Service;

use App\Repository\PaymentRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class Payouts
{
    public function payout(PaymentRepository $paymentRepository, UserInterface $user): array
    {
        $data = $paymentRepository->getDividendsPerInterval($user, 'Month');
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
            'data' => $data,
            'labels' => $labels,
            'dividends' => $dividends,
            'accumulative' => $accumulative,
        ];
    }
}
