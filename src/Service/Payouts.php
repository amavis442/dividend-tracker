<?php

namespace App\Service;

use App\Repository\PaymentRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class Payouts
{
    public function payout(PaymentRepository $paymentRepository, UserInterface $user): array
    {
        if (!$user instanceof \App\Entity\User) {
            throw new \RuntimeException("User not known");
        }

        $data = $paymentRepository->getDividendsPerInterval($user, 'Month');
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
}
