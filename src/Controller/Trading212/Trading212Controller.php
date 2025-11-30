<?php

namespace App\Controller\Trading212;

use App\Repository\Trading212PieMetaDataRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use App\Service\Trading212\CalcStatsService;

#[
	Route(
		path: '/{_locale<%app.supported_locales%>}/dashboard/trading212/report'
	)
]
final class Trading212Controller extends AbstractController
{
	public function __construct(protected CalcStatsService $calcStatsService)
	{
	}

	#[Route('/', name: 'app_report_trading212_index')]
	public function index(
		Trading212PieMetaDataRepository $trading212PieMetaDataRepository
	): Response {
		$data = $trading212PieMetaDataRepository->latest();

		$stats = $this->calcStatsService->calc($data);
		return $this->render(
			'trading212/report/index.html.twig',
			array_merge(
				[
					'title' => 'Trading212',
					'data' => $data,
				],
				$stats
			)
		);
	}

	#[Route('/summary', name: 'app_report_trading212_summary')]
	public function summary(
		Trading212PieMetaDataRepository $trading212PieMetaDataRepository,
		ChartBuilderInterface $chartBuilder,
		TranslatorInterface $translator
	): Response {
		$data = $trading212PieMetaDataRepository->latest();
		$stats = $this->calcStatsService->calc($data);

		$summary = [];
		$summary['label'] = [];
		$summary['invested'] = [];
		$summary['price'] = [];
		$summary['gained'] = [];
		$summary['totalReturn'] = [];
		$summary['breakEven'] = [];
		$summaryData = $trading212PieMetaDataRepository->getSummary();
		foreach ($summaryData as $createdAt => $item) {
			$summary['label'][] = $item['createdAt']->format('Y-m-d');

			$invested = round($item['invested'], 2);
			$summary['invested'][] = $invested;

			$price = round($item['price'], 2);
			$summary['price'][] = $price;

			$gained = round($item['dividend'], 2);
			$summary['gained'][] = $gained;

			$summary['totalReturn'][] = $price + $gained;

			$summary['breakEven'][] = $invested - $price - $gained;
		}

		$summaryChart = $this->summaryChart(
			$summary,
			$chartBuilder,
			$translator
		);
		$summaryBreakEvenChart = $this->summaryBreakEvenChart(
			$summary,
			$chartBuilder,
			$translator
		);

		return $this->render(
			'trading212/report/summary_chart.html.twig',
			array_merge(
				[
					'summaryChart' => $summaryChart,
					'breakEvenChart' => $summaryBreakEvenChart,
				],
				$stats
			)
		);
	}

	protected function summaryChart(
		array $summary,
		ChartBuilderInterface $chartBuilder,
		TranslatorInterface $translator
	) {
		$chart = $chartBuilder->createChart(Chart::TYPE_LINE);
		$chart->setData([
			'labels' => $summary['label'],
			'datasets' => [
				[
					'label' => $translator->trans('Invested'),
					'data' => $summary['invested'],
				],
				[
					'label' => $translator->trans('Price'),
					'data' => $summary['price'],
				],
				[
					'label' => $translator->trans('Gained'),
					'data' => $summary['gained'],
				],
				[
					'label' => $translator->trans('Total return'),
					'data' => $summary['totalReturn'],
				],
			],
		]);

		$chart->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => $translator->trans(
						'Break even (under zero is good)'
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

		return $chart;
	}

	protected function summaryBreakEvenChart(
		array $summary,
		ChartBuilderInterface $chartBuilder,
		TranslatorInterface $translator
	) {
		$chart = $chartBuilder->createChart(Chart::TYPE_LINE);
		$chart->setData([
			'labels' => $summary['label'],
			'datasets' => [
				[
					'label' => $translator->trans('Break even'),
					'data' => $summary['breakEven'],
				],
			],
		]);

		$chart->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => $translator->trans(
						'Break even (under zero is good)'
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

		return $chart;
	}
}
