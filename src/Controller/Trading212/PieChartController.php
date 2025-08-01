<?php

namespace App\Controller\Trading212;

use App\Entity\Pie;
use App\Helper\Colors;
use App\Repository\CalendarRepository;
use App\Repository\PaymentRepository;
use App\Repository\Trading212PieInstrumentRepository;
use App\Repository\Trading212PieMetaDataRepository;
use App\Service\ExchangeRate\ExchangeRateInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[
	Route(
		path: '/{_locale<%app.supported_locales%>}/dashboard/trading212/report'
	)
]
final class PieChartController extends AbstractController
{
	#[Route('/pie-chart', name: 'app_report_trading212_portfolio_index')]
	public function index(
		Trading212PieMetaDataRepository $trading212PieMetaDataRepository,
		ChartBuilderInterface $chartBuilder,
		TranslatorInterface $translator
	): Response {
		$pieChart = $this->createPieChart(
			$trading212PieMetaDataRepository,
			$chartBuilder,
			$translator
		);

		$charts = $this->createChartSummary(
			$trading212PieMetaDataRepository,
			$chartBuilder,
			$translator
		);
		$chart = $charts['summaryChart'];
		$trChart = $charts['totalReturnChart'];

		return $this->render('trading212/report/pie_chart.html.twig', [
			'pieChart' => $pieChart,
			'chart' => $chart,
			'trChart' => $trChart,
		]);
	}

	private function createPieChart(
		Trading212PieMetaDataRepository $trading212PieMetaDataRepository,
		ChartBuilderInterface $chartBuilder,
		TranslatorInterface $translator
	): \Symfony\UX\Chartjs\Model\Chart {
		$data = $trading212PieMetaDataRepository->latest();

		$totalInvested = 0.0;
		/**
		 * @var \App\Entity\Trading212PieMetaData $pie
		 */
		foreach ($data as $pie) {
			$totalInvested += $pie->getPriceAvgInvestedValue();
		}
		$labels = [];
		$chartData = [];
		foreach ($data as $pie) {
			$totalInvested += $pie->getPriceAvgInvestedValue();
			$labels[] = $pie->getPieName();
			$chartData[] = round(
				($pie->getPriceAvgInvestedValue() / $totalInvested) * 100,
				2
			);
		}
		$pieChart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
		$pieChart->setData([
			'labels' => $labels,
			'datasets' => [
				[
					'label' => 'Percentage',
					'data' => $chartData,
					'fill' => false,
				],
			],
		]);
		$pieChart->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => $translator->trans('Pie allocations'),
					'font' => [
						'size' => 24,
					],
				],
				'legend' => [
					'position' => 'top',
				],
			],
		]);

		return $pieChart;
	}

	private function createChartSummary(
		Trading212PieMetaDataRepository $trading212PieMetaDataRepository,
		ChartBuilderInterface $chartBuilder,
		TranslatorInterface $translator
	): array {
		$dt = new \DateTime('first day of january');
		$data = $trading212PieMetaDataRepository->getSumAllocatedAndDistributedPerData(
			$dt
		);
		$invested = [];
		$gained = [];
		$reinvested = [];
		$currentValue = [];
		$pieDates = [];
		$totalReturn = [];
		$trPercentage = []; // totalreturn Percentage
		foreach ($data as $item) {
			$invested[] = $item['invested'] ?: 0;
			$gained[] = $item['gained'] ?: 0;
			$reinvested[] = $item['reinvested'] ?: 0;
			$currentValue[] = $item['currentvalue'] ?: 0;
			$totalReturn[] = $item['currentvalue'] + $item['gained'];
			$trPercentage[] =
				$item['invested'] > 0
					? (($item['currentvalue'] +
							$item['gained'] -
							$item['invested']) /
							$item['invested']) *
						100
					: 0.0;
			$pieDates[] = $item['createdAt']->format('Y-m-d');
		}

		$chart = $chartBuilder->createChart(Chart::TYPE_LINE);
		$chart->setData([
			'labels' => $pieDates,
			'datasets' => [
				[
					'label' => $translator->trans('Invested'),
					'data' => $invested,
				],
				[
					'label' => $translator->trans('Gained'),
					'data' => $gained,
				],
				[
					'label' => $translator->trans('Reinvested'),
					'data' => $reinvested,
				],
				[
					'label' => $translator->trans('Current value'),
					'data' => $currentValue,
				],
				[
					'label' => $translator->trans('Total return'),
					'data' => $totalReturn,
				],
			],
		]);

		$chart->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => $translator->trans('Summary'),
					'font' => [
						'size' => 24,
					],
				],
				'legend' => [
					'position' => 'top',
				],
			],
		]);

		$trChart = $this->createTotalReturnChart(
			$trPercentage,
			$pieDates,
			$chartBuilder,
			$translator
		);

		return ['summaryChart' => $chart, 'totalReturnChart' => $trChart];
	}

	private function createTotalReturnChart(
		array $trPercentage,
		array $pieDates,
		ChartBuilderInterface $chartBuilder,
		TranslatorInterface $translator
	): \Symfony\UX\Chartjs\Model\Chart {
		$chart = $chartBuilder->createChart(Chart::TYPE_LINE);
		$chart->setData([
			'labels' => $pieDates,
			'datasets' => [
				[
					'label' => $translator->trans('Total return %'),
					'data' => $trPercentage,
				],
			],
		]);

		$chart->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => $translator->trans('Total return %'),
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
