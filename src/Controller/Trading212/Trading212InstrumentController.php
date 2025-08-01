<?php

namespace App\Controller\Trading212;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Chartjs\Model\Chart;

use App\Repository\Trading212PieInstrumentRepository;
use App\Entity\Ticker;
use App\Entity\Pie;
use App\Entity\Trading212PieInstrument;

#[
	Route(
		path: '/{_locale<%app.supported_locales%>}/dashboard/trading212/report'
	)
]
final class Trading212InstrumentController extends AbstractController
{
	public const INCOMESHARES_PAIRS = [];

	#[Route('/{pie}/{ticker}', name: 'app_report_trading212_instrument_index')]
	public function index(
		Pie $pie,
		Ticker $ticker,
		Trading212PieInstrumentRepository $instrumentRepository,
		ChartBuilderInterface $chartBuilder,
		TranslatorInterface $translator
	): Response {
		/**
		 * array<int, Trading212PieInstrument> $data
		 */
		$data = $instrumentRepository->findByTicker($ticker, $pie);

		$dataChart = [];
		$dataChart['labels'] = [];
		$dataChart['data'] = [];
		$dataChart['data']['invested'] = [];
		$dataChart['data']['value'] = [];
		$dataChart['data']['result'] = [];
		$dataChart['data']['total_return'] = [];
		$dataChart['data']['profiloss'] = [];

		/**
		 * @var Trading212PieInstrument $instrument
		 */
		foreach ($data as $instrument) {
			$dataChart['labels'][] = $instrument['createdAt'];
			$dataChart['data']['invested'][] = $instrument['invested'];
			$dataChart['data']['value'][] = $instrument['value'];
			//$dataChart['data']['result'][] = $instrument->getPriceAvgResult();

			$dataChart['data']['profiloss'][] = $instrument['value'] - $instrument['invested'];
		}

		$chart = $chartBuilder->createChart(Chart::TYPE_LINE);
		$chart->setData([
			'labels' => $dataChart['labels'],
			'datasets' => [
				[
					'label' => $translator->trans('Invested'),
					'data' => $dataChart['data']['invested'],
				],
				[
					'label' => $translator->trans('Value'),
					'data' => $dataChart['data']['value'],
				],
			],
		]);

		$chart->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => $translator->trans($ticker->getFullname()),
					'font' => [
						'size' => 24,
					],
				],
				'legend' => [
					'position' => 'top',
				],
			],
		]);

		$chartProfitLoss = $chartBuilder->createChart(Chart::TYPE_LINE);
		$chartProfitLoss->setData([
			'labels' => $dataChart['labels'],
			'datasets' => [
				[
					'label' => $translator->trans('Profit/Loss'),
					'data' => $dataChart['data']['profiloss'],
				],
			],
		]);

		$chartProfitLoss->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => $translator->trans($ticker->getFullname()),
					'font' => [
						'size' => 24,
					],
				],
				'legend' => [
					'position' => 'top',
				],
			],
		]);

		return $this->render(
			'trading212/report/instrument.html.twig',

			[
				'title' => 'Trading212',
				'chart' => $chart,
				'chartProfitLoss' => $chartProfitLoss,
				'pie' => $pie,
			]
		);
	}
}
