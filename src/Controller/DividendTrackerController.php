<?php

namespace App\Controller;

use App\Helper\Colors;
use App\Repository\PaymentRepository;
use App\Repository\DividendTrackerRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ExpressionBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/tracker')]
class DividendTrackerController extends AbstractController
{
	#[
		Route(
			path: '/dividend/{year}',
			name: 'dividend_tracker',
			requirements: ['year' => '\d+']
		)
	]
	public function index(
		DividendTrackerRepository $dividendTrackerRepository,
		PaymentRepository $paymentRepository,
		TranslatorInterface $translator,
		ChartBuilderInterface $chartBuilder,
		int $year = 1
	): Response {
		if ($year == 1) {
			$year = date('Y');
		}
		$year -= 1;

		$payments = $paymentRepository->getSumPaymentsPerMonth($year + 1);

		$startDate = new \DateTime($year . '-12-31 00:00:00');
		$expression = (new ExpressionBuilder())->gt('sampleDate', $startDate);
		$criteria = new Criteria($expression);

		$data = $dividendTrackerRepository->matching($criteria);
		$labels = [];
		$dividendData = [];
		$principleData = [];
		$receivedDividends = [];
		$yieldReceivedDividends = [];

		$colors = Colors::COLORS;

		foreach ($data as $item) {
			$pKey = $item->getSampleDate()->format('Ym');
			if (isset($payments[$pKey])) {
				$receivedDividends[$pKey] = $payments[$pKey];
				$yieldReceivedDividends[$pKey] = round(
					(($payments[$pKey] * 12) / $item->getPrinciple()) * 100,
					2
				);
			} else {
				$yieldReceivedDividends[$pKey] = 0.0;
				$receivedDividends[$pKey] = 0.0;
			}
			$dividendData[$pKey] = round($item->getDividend(), 2);
			$principleData[$pKey] = round($item->getPrinciple(), 2);
			$labels[$pKey] = $item->getSampleDate()->format('m-Y');
		}
		ksort($dividendData);
		ksort($principleData);
		ksort($labels);

		$chartData = [
			[
				'label' => $translator->trans('Expected dividend'),
				'data' => array_values($dividendData),
			],
			[
				'label' => $translator->trans('Principle'),
				'data' => array_values($principleData),
			],
		];

		$colors = Colors::COLORS;

		$chart = $chartBuilder->createChart(Chart::TYPE_BAR);

		$chart->setData([
			'labels' => array_values($labels),
			'datasets' => [
				[
					'label' => $chartData[0]['label'],
					'backgroundColor' => $colors[0],
					'borderColor' => $colors,
					'data' => $chartData[0]['data'],
				],
				[
					'label' => $chartData[1]['label'],
					'backgroundColor' => $colors[1],
					'borderColor' => $colors,
					'data' => $chartData[1]['data'],
				],
			],
		]);

		$chart->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => $translator->trans('Expected dividend'),
					'font' => [
						'size' => 24,
					],
				],
				'legend' => [
					'position' => 'top',
				],
			],
		]);

		$chartYield = $this->yieldChart(
			$translator,
			$chartBuilder,
			$labels,
			$yieldReceivedDividends
		);

		$chartRealized = $this->realizedDividendsChart(
			$translator,
			$chartBuilder,
			$labels,
			$receivedDividends
		);

		return $this->render('dividend_tracker/index.html.twig', [
			'controller_name' => 'DividendController',
			'chart' => $chart,
			'chartYield' => $chartYield,
			'chartRealized' => $chartRealized,
		]);
	}

	private function yieldChart(
		TranslatorInterface $translator,
		ChartBuilderInterface $chartBuilder,
		array $labels,
		array $yieldReceivedDividends
	): \Symfony\UX\Chartjs\Model\Chart {
		ksort($yieldReceivedDividends);

		$chartYield = $chartBuilder->createChart(Chart::TYPE_BAR);
		$chartYield->setData([
			'labels' => array_values($labels),
			'datasets' => [
				[
					'label' => $translator->trans('Yield'),
					'data' => array_values($yieldReceivedDividends),
					'backgroundColor' => ['#ffbe00'],
				],
			],
		]);
		$chartYield->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => $translator->trans(
						'Realized dividend yield per year'
					),
					'font' => [
						'size' => 24,
					],
				],
				'legend' => [
					'position' => 'top',
				],
			],
		]);
		return $chartYield;
	}

	private function realizedDividendsChart(
		TranslatorInterface $translator,
		ChartBuilderInterface $chartBuilder,
		array $labels,
		array $receivedDividends
	): \Symfony\UX\Chartjs\Model\Chart {
		ksort($receivedDividends);

		$chartRealized = $chartBuilder->createChart(Chart::TYPE_BAR);
		$chartRealized->setData([
			'labels' => array_values($labels),
			'datasets' => [
				[
					'label' => $translator->trans('Received'),
					'data' => array_values($receivedDividends),
					'backgroundColor' => ['#40ac1c'],
				],
			],
		]);
		$chartRealized->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => $translator->trans('Received dividend per month'),
					'font' => [
						'size' => 24,
					],
				],
				'legend' => [
					'position' => 'top',
				],
			],
		]);

		return $chartRealized;
	}
}
