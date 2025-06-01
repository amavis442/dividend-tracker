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
		$dates = [];
		$dividends = [];
		foreach ($data as $item) {
			$labels[] = sprintf(
				'%04d-%02d',
				$item['periodYear'],
				$item['periodMonth']
			);
			$dates[] = (int)sprintf(
				'%04d%02d',
				$item['periodYear'],
				$item['periodMonth']
			);
			$dividends[] = $item['dividend'];
		}

		$trendarray = $this->trendline(array_keys($dividends), $dividends);
		$trendline = [];
		foreach ( array_keys($dividends) as $item ) {
     		$number = ( $trendarray['slope'] * $item ) + $trendarray['intercept'];
     		$number = ( $number <= 0 )? 0 : $number;
			$trendline[] = $number;
		}
		return [
			'labels' => $labels,
			'dividends' => $dividends,
			'trendline' => $trendline,
		];
	}

	private function trendline($x, $y)
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

		// Slope
		$slope =
			($n * $xy_sum - $x_sum * $y_sum) / ($n * $xx_sum - $x_sum * $x_sum);

		// calculate intercept
		$intercept = ($y_sum - $slope * $x_sum) / $n;

		return [
			'slope' => $slope,
			'intercept' => $intercept,
		];
	}
}
