<?php

namespace App\Controller\Report;

use App\DataProvider\BasicDatasetDataProvider;

use App\Helper\Colors;
use App\Service\Dividend\DividendYieldCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use App\Enum\SortDirection;
use App\Enum\SortField;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/report')]
class YieldByPieController extends AbstractController
{
	public const TAX_DIVIDEND = 0.15; // %
	public const EXCHANGE_RATE = 1.19; // dollar to euro

	#[Route(path: '/pieyield', name: 'report_dividend_yield_by_pie')]
	public function index(
		ChartBuilderInterface $chartBuilder,
		DividendYieldCalculator $dividendYieldCalculator,
		BasicDatasetDataProvider $basicDatasetDataProvider,
		#[MapQueryParameter] string $sortBy = 'symbol',
		#[MapQueryParameter] string $sortDirection = 'asc'
	): Response {
		$sortBy = SortField::fromString($sortBy);
		$sortDirection = SortDirection::fromString($sortDirection);

		$dataSet = $basicDatasetDataProvider->getDataForYield();
		$tickers = $dataSet->tickers;
		$calendars = $dataSet->calendars;
		$positions = $dataSet->positions;
		$exchangeRates = $dataSet->exchangeRates;

		$dividendYieldCalculator->load(
			tickers: $tickers,
			calendars: $calendars,
			positions: $positions,
			exchangeRates: $exchangeRates
		);

		$dataCalc = $dividendYieldCalculator->process();

		$totalDividendPerMonth = 0.0;
		$totalInvested = 0.0;
		$result = [];

		BasicDatasetDataProvider::sort($dataCalc,$sortBy, $sortDirection);

		$result['datasource'] = $dataCalc;

		foreach ($dataCalc as $index => $item) {
			$ticker = $item['ticker'];
			$dividendPerMonth = $item['cash']['all_shares']['month']['net'];
			$invested = $item['invested'];
			$yieldPerYear = ((12 * $dividendPerMonth) / $invested) * 100;

			$totalDividendPerMonth += $dividendPerMonth;
			$totalInvested += $invested;


			$result['labels'][] = $ticker->getFullname();
			$result['data'][] = $yieldPerYear;
		}
		$yearlyEstimatedDividend = $totalDividendPerMonth * 12;
		$yearlyEstimatedYield =
			($yearlyEstimatedDividend / $totalInvested) * 100;

		$result['totalNetYearlyDividend'] = $yearlyEstimatedDividend;
		$result['dividendYieldOnCost'] = $yearlyEstimatedYield;
		$result['allocated'] = $totalInvested;

		// $result = $yields->yield($sort, $sortDirection, null);

		$colors = Colors::COLORS;

		$chart = $chartBuilder->createChart(Chart::TYPE_BAR);
		if (count($result) > 0) {
			$chart->setData([
				'labels' => $result['labels'],
				'datasets' => [
					[
						'label' => 'Dividend yield',
						'backgroundColor' => $colors,
						'borderColor' => $colors,
						'data' => $result['data'],
					],
				],
			]);

			$chart->setOptions([
				'maintainAspectRatio' => false,
				'responsive' => true,
				'plugins' => [
					'title' => [
						'display' => true,
						'text' => 'Yield',
						'font' => [
							'size' => 24,
						],
					],
					'legend' => [
						'position' => 'top',
					],
				],
			]);
		}
		// return $this->render(
		// 	'report/yield/pie.html.twig',
		// 	array_merge($result, [
		// 		'sort' => $sort,
		// 		'sortDirection' => $sortDirection,
		// 		'chart' => $chart,
		// 	])
		// );

		return $this->render(
			'report/yield/pie2.html.twig',
			array_merge($result, [
				'sortBy' => $sortBy->toString(),
				'sortDirection' => $sortDirection->toString(),
				'chart' => $chart,
				])
		);
	}
}
