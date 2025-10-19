<?php

namespace App\Controller\Report;

use App\Helper\Colors;
use App\Repository\PaymentRepository;
use App\Repository\DividendTrackerRepository;
use App\Repository\CalendarRepository;
use App\Service\Payouts;
use App\Service\Dividend\DividendService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/report')]
class PayoutController extends AbstractController
{
	//public const TAX_DIVIDEND = 0.15; // %
	//public const EXCHANGE_RATE = 1.19; // dollar to euro
	public const YIELD_PIE_KEY = 'yeildpie_searchPie';

	#[Route(path: '/payout', name: 'report_payout')]
	public function index(
		PaymentRepository $paymentRepository,
		Payouts $payout,
		ChartBuilderInterface $chartBuilder
	): Response {
		$result = $payout->payout($paymentRepository);
		$colors = Colors::COLORS;

		$chart = $chartBuilder->createChart(Chart::TYPE_BAR);

		$chart->setData([
			'labels' => $result['labels'],
			'datasets' => [
				[
					'label' => 'Dividend payout',
					'backgroundColor' => $colors[0],
					'borderColor' => $colors[0],
					'data' => $result['dividends'],
				],
				[
					'label' => 'Accumulative',
					'backgroundColor' => $colors[1],
					'borderColor' => $colors[1],
					'data' => $result['accumulative'],
				],
			],
		]);

		$chart->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => 'Dividends payout',
					'font' => [
						'size' => 24,
					],
				],
				'legend' => [
					'position' => 'top',
				],
			],
		]);

		return $this->render('report/payout/index.html.twig', [
			'controller_name' => 'ReportController',
			'chart' => $chart,
		]);
	}

	#[Route(path: '/payments', name: 'report_payments')]
	public function payments(
		PaymentRepository $paymentRepository,
		DividendTrackerRepository $dividendTrackerRepository,
		CalendarRepository $calendarRepository,
		Payouts $payout,
		DividendService $dividendService,
		ChartBuilderInterface $chartBuilder
	): Response {
		$result = $payout->payments($paymentRepository,$dividendTrackerRepository, $dividendService, $calendarRepository);
		$chart = $chartBuilder->createChart(Chart::TYPE_BAR);
		$chart->setData([
			'labels' => $result['labels'],
			'datasets' => [
				[
					'label' => 'Dividend payout',
					'data' => $result['dividends'],
				],
				[
					'label' => 'forecast payout',
					'data' => array_values($result['forecast'])
				],
				[
					'label' => 'Trendline',
					'data' => $result['trendline'],
					'type' => 'line',
				],
			],
		]);

		$chart->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => 'Dividends payout',
					'font' => [
						'size' => 24,
					],
				],
				'legend' => [
					'position' => 'top',
				],
			],
		]);

		$yieldChart = $this->yields($result['yields'],$result['trendlineYield'], $chartBuilder);

		return $this->render('report/payout/index.html.twig', [
			'controller_name' => 'ReportController',
			'chart' => $chart,
			'yieldChart' => $yieldChart,
		]);
	}

	protected function yields(array $data, array $trendline, ChartBuilderInterface $chartBuilder) {
		$chart = $chartBuilder->createChart(Chart::TYPE_BAR);
		$colors = [];
		for ($i = 0; $i < count($data)-1;$i++) {
			$colors[] = 'rgba(54, 162, 235, 0.8)';
		}
		$colors[] = 'rgba(255, 99, 132, 0.8)';

		$chart->setData([
			'labels' => array_keys($data),
			'datasets' => [
				[
					'label' => 'Dividend yield',
					'data' => array_values($data),
					'backgroundColor'=> $colors,
				],
				[
					'label' => 'Trendline',
					'data' =>  $trendline,
					'type' => 'line',
					'borderColor' =>  'rgb(75, 192, 192)',
				],
			],
		]);

		$chart->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => 'Dividends payout',
					'font' => [
						'size' => 24,
					],
				],
				'legend' => [
					'position' => 'top',
				],
			],
		]);

		return $chart;
	}
}
