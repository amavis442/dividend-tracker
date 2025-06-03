<?php

namespace App\Service;

use App\Repository\PaymentRepository;
use App\Repository\DividendTrackerRepository;
use App\Repository\CalendarRepository;
use App\Service\DividendService;

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

	public function payments(PaymentRepository $paymentRepository, DividendTrackerRepository $dividendTrackerRepository,
	DividendService $dividendService,CalendarRepository $calendarRepository): array
	{
		$paydate = (new \DateTime('first day of this month -1 years'))->format(
			'Y-m-d'
		);
		$data = $paymentRepository->getSumPayments($paydate);
		$alloctionData = $dividendTrackerRepository->getAllocationsPerMonth($paydate);
		$allocations = [];
		foreach ($alloctionData as $item) {
			$id = (int)sprintf(
				'%04d%02d',
				$item['periodYear'],
				$item['periodMonth']
			);
			$allocations[$id] = $item['allocation'];
		}

		//$startDate = (new \DateTime('first day of this month'))->format('Y-m-d');
		$endDate = (new \DateTime('last day of this month'))->format('Y-m-d');
		$foreCastDividend = $calendarRepository->foreCast($dividendService, $paydate, $endDate);
		ksort($foreCastDividend);

		$labels = [];
		$dates = [];
		$dividends = [];
		$foreCastYields = [];
		$yields = [];
		foreach ($data as $item) {
			$labels[] = sprintf(
				'%04d-%02d',
				$item['periodYear'],
				$item['periodMonth']
			);
			$date = (int)sprintf(
				'%04d%02d',
				$item['periodYear'],
				$item['periodMonth']
			);

			$dates[] = $date;
			$dividends[] = $item['dividend'];

			if (isset($allocations[$date])) {
				$yields[$date] = (($item['dividend'] * 12) / $allocations[$date])*100;

				if (isset($foreCastDividend[$date])) {
					$foreCastYields[$date] = (($foreCastDividend[$date] * 12) / $allocations[$date])*100;
				}
			}
		}

		$labelMonths = array_fill_keys($labels, 'filler');

		$date = date('Y-m');
		if (!isset($labelMonths[$date])) {
			$labels[] = $date;
			$yields[$date] = 0.0;
		}
		$date = date('Ym');
		if (isset($allocations[$date]) && isset($foreCastDividend[$date])) {
			$yields[$date] = (($foreCastDividend[$date] * 12) / $allocations[$date])*100;
		}

		$trendarray = $this->trendline(array_keys($dividends), $dividends);
		$trendline = [];
		foreach ( array_keys($dividends) as $item ) {
     		$number = ( $trendarray['slope'] * $item ) + $trendarray['intercept'];
     		$number = ( $number <= 0 )? 0 : $number;
			$trendline[] = $number;
		}

		$range = range(0,count($yields)-1);
		$trendarrayYield = $this->trendline($range, array_values($yields));
		$trendlineYield = [];
		foreach ( $range as $item ) {
     		$number = ( $trendarrayYield['slope'] * $item ) + $trendarrayYield['intercept'];
     		$number = ( $number <= 0 )? 0 : $number;
			$trendlineYield[] = $number;
		}

		$range = range(0,count($foreCastYields)-1);
		$trendarrayYield = $this->trendline($range, array_values($foreCastYields));
		$trendlineForeCastYield = [];
		foreach ( $range as $item ) {
     		$number = ( $trendarrayYield['slope'] * $item ) + $trendarrayYield['intercept'];
     		$number = ( $number <= 0 )? 0 : $number;
			$trendlineForeCastYield[] = $number;
		}


		return [
			'labels' => $labels,
			'dividends' => $dividends,
			'trendline' => $trendline,
			'yields' => $yields,
			'trendlineYield' => $trendlineYield,
			'forecast' => $foreCastDividend,
			'trendlineForeCastYield' => $trendlineForeCastYield,
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
