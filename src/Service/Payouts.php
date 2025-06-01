<?php

namespace App\Service;

use App\Repository\PaymentRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class Payouts
{
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

	public function payments(PaymentRepository $paymentRepository): array
	{
		$paydate = (new \DateTime('first day of this month -1 years'))->format(
			'Y-m-d'
		);
		$data = $paymentRepository->getSumPayments($paydate);

		$labels = [];
		$dividends = [];
		foreach ($data as $item) {
			$labels[] = sprintf(
				'%04d-%02d',
				$item['periodYear'],
				$item['periodMonth']
			);
			$dividends[] = $item['dividend'];
		}

		return [
			'labels' => $labels,
			'dividends' => $dividends,
		];
	}
}
