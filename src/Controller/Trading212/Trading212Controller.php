<?php

namespace App\Controller\Trading212;

use App\Entity\Pie;
use App\Helper\Colors;
use App\Repository\CalendarRepository;
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
final class Trading212Controller extends AbstractController
{
	#[Route('/', name: 'app_report_trading212_index')]
	public function index(
		Trading212PieMetaDataRepository $trading212PieMetaDataRepository,
		#[MapQueryParameter] int $page = 1
	): Response {
		$queryBuilder = $trading212PieMetaDataRepository->all();

		$adapter = new QueryAdapter($queryBuilder);
		$pager = new Pagerfanta($adapter);
		$pager->setMaxPerPage(10);
		$pager->setCurrentPage($page);

		return $this->render('trading212/report/index.html.twig', [
			'title' => 'Trading212Controller',
			'pager' => $pager,
		]);
	}

	#[Route('/graph/{pie}', name: 'app_report_trading212_graph')]
	public function graph(
		Pie $pie,
		Trading212PieMetaDataRepository $trading212PieMetaDataRepository,
		CalendarRepository $calendarRepository,
		TranslatorInterface $translator,
		ExchangeRateInterface $exchangeRate,
		ChartBuilderInterface $chartBuilder
	): Response {

		/**
		 * @var \App\Entity\Trading212PieMetaData $metaData
		 */
		$metaData = $trading212PieMetaDataRepository->findOneBy(
			['pie' => $pie],
			['createdAt' => 'DESC']
		);
		$instruments = $metaData->getTrading212PieInstruments();
		$pieAvgInvested = $metaData->getPriceAvgInvestedValue();
		$rates = $exchangeRate->getRates();
		$rateDollarEuro = 1 / $rates['USD'];

		$pieInstruments = [];

		/**
		 * @var \App\Entity\Trading212PieInstrument $instrument
		 */
		foreach ($instruments as $instrument) {
			$ticker = $instrument->getTicker();
			if (!$ticker || $instrument->getPriceAvgInvestedValue() == 0) continue;
			$tax = $ticker->getTax()->getTaxRate() / 100;

			$owned = $instrument->getOwnedQuantity();
			// Current
			$currentDividend = $calendarRepository->getCurrentDividend($ticker);
			$instrument->setCurrentDividendPerShare($currentDividend);

			$totalCurrentDividend =
				$currentDividend * $owned * (1 - $tax) * $rateDollarEuro;
			$instrument->setCurrentDividend($totalCurrentDividend);

			$currentYearlYield = (($ticker->getPayoutFrequency() * $totalCurrentDividend) / $instrument->getPriceAvgInvestedValue()) *100;
			$instrument->setCurrentYearlyYield($currentYearlYield);

			// Avg
			$avgDividend = $calendarRepository->getAvgDividend($ticker);
			$instrument->setAvgDividendPerShare($avgDividend);

			$avgExpectedDividend =
				$avgDividend * $owned * (1 - $tax) * $rateDollarEuro;
			$instrument->setAvgExpectedDividend($avgExpectedDividend);

			$avgYearlYield = (($ticker->getPayoutFrequency() * $avgExpectedDividend) / $instrument->getPriceAvgInvestedValue()) *100;
			$instrument->setAvgYearlyYield($avgYearlYield);

			$pieShare = round(($instrument->getPriceAvgInvestedValue() / $pieAvgInvested) * 100, 2);
			$pieInstruments['labels'][] = $ticker->getFullname();
			$pieInstruments['data'][] = $pieShare;

		}

		$chartInstruments = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
		$chartInstruments->setData([
			'labels' => $pieInstruments['labels'],
			'datasets' => [
				[
					'label' => 'Percentage',
					'data' => $pieInstruments['data'],
				],
			],
		]);

		$labels = [];
		$allocationData = [];
		$valueData = [];
		$gained = [];

		$colors = Colors::COLORS;

		$data = $trading212PieMetaDataRepository->findBy(
			['pie' => $pie],
			['createdAt' => 'ASC']
		);

		/**
		 * @var \App\Entity\Trading212PieMetaData $item
		 */
		foreach ($data as $item) {
			$allocationData[] = round($item->getPriceAvgInvestedValue(), 2);
			$valueData[] = round($item->getPriceAvgValue(), 2);
			$gained[] = round($item->getGained(), 2);
			$labels[] = $item->getCreatedAt()->format('d-m-Y');
		}
		$chartData = [
			[
				'label' => $translator->trans('Invested'),
				'data' => $allocationData,
			],
			[
				'label' => $translator->trans('Current value'),
				'data' => $valueData,
			],
			[
				'label' => $translator->trans('Dividend'),
				'data' => $gained,
			],
		];

		$chart = $chartBuilder->createChart(Chart::TYPE_LINE);
		$chart->setData([
			'labels' => $labels,
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
				[
					'label' => $chartData[2]['label'],
					'backgroundColor' => $colors[2],
					'borderColor' => $colors,
					'data' => $chartData[2]['data'],
				],
			],
		]);

		$chart->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => $translator->trans($pie->getLabel()),
					'font' => [
						'size' => 24,
					],
				],
				'legend' => [
					'position' => 'top',
				],
			],
		]);

		//$trading212PieInstrumentRepository;

		return $this->render('trading212/report/graph.html.twig', [
			'title' => 'Trading212Controller',
			'chart' => $chart,
			'instruments' => $instruments,
			'chartInstruments' => $chartInstruments,
		]);
	}
}
