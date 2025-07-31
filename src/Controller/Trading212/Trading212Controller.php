<?php

namespace App\Controller\Trading212;

use App\Entity\Pie;
use App\Entity\Ticker;
use App\Entity\Trading212PieMetaData;
use App\Helper\Colors;
use App\Repository\DividendCalendarRepository;
use App\Repository\PaymentRepository;
use App\Repository\TickerRepository;
use App\Repository\Trading212PieMetaDataRepository;
use App\Service\ExchangeRate\ExchangeRateInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Doctrine\Common\Collections\Collection;

#[
	Route(
		path: '/{_locale<%app.supported_locales%>}/dashboard/trading212/report'
	)
]
final class Trading212Controller extends AbstractController
{
	#[Route('/', name: 'app_report_trading212_index')]
	public function index(
		Trading212PieMetaDataRepository $trading212PieMetaDataRepository
	): Response {
		$data = $trading212PieMetaDataRepository->latest();

		$stats = $this->calcStats($data);
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

	/**
	 * array<Trading212PieMetaData>|Trading212PieMetaData $data
	 */
	private function calcStats(array|Trading212PieMetaData $data): array
	{
		$totalInvested = 0.0;
		$totalValue = 0.0;
		$totalGained = 0.0;
		$totalGainedYield = 0.0;
		$totalReturn = 0.0;
		$totalReturnYield = 0.0;
		$profitLoss = 0.0;
		$profitLossPercentage = 0.0;

		if ($data instanceof Trading212PieMetaData) {
			$totalInvested = $data->getPriceAvgInvestedValue();
			$totalValue = $data->getPriceAvgValue();
			$totalGained = $data->getGained();
		}
		if (is_array($data)) {
			foreach ($data as $item) {
				$totalInvested += $item->getPriceAvgInvestedValue();
				$totalValue += $item->getPriceAvgValue();
				$totalGained += $item->getGained();
			}
		}
		$totalReturn = $totalValue + $totalGained - $totalInvested;
		$totalReturnYield =
			$totalInvested > 0 ? ($totalReturn / $totalInvested) * 100 : 0.0;
		$totalGainedYield =
			$totalInvested > 0 ? ($totalGained / $totalInvested) * 100 : 0.0;
		$profitLoss = $totalValue - $totalInvested;
		$profitLossPercentage =
			$totalInvested > 0 ? ($profitLoss / $totalInvested) * 100 : 0.0;
		return [
			'totalInvested' => $totalInvested,
			'totalValue' => $totalValue,
			'totalGained' => $totalGained,
			'totalGainedYield' => $totalGainedYield,
			'totalReturn' => $totalReturn,
			'totalReturnYield' => $totalReturnYield,
			'profitLoss' => $profitLoss,
			'profitLossPercentage' => $profitLossPercentage,
		];
	}

	protected function decorateWithDividend(
		DividendCalendarRepository $calendarRepository,
		array &$tickers,
		float $rateDollarEuro
	): void {
		$lastYear = sprintf(
			'%04d-%02d-%02d',
			date('Y') - 1,
			date('m'),
			date('d')
		);

		// Get Calendars for at least 1 year
		$tickerCalendars = $calendarRepository->getCalendarsForTickers(
			$tickers,
			$lastYear
		);
		$lastYear = (new \Datetime('-1 years -1 months'))->format('Ym');

		foreach ($tickerCalendars as $tickerCalendar) {
			$id = $tickerCalendar->getTicker()->getId();
			$frequency = $tickerCalendar->getTicker()->getPayoutFrequency();
			$cId = (int) $tickerCalendar->getPaymentDate()->format('Ym');
			$tickers[$id]['calendars'][$cId] = $tickerCalendar;

			if (!isset($tickers[$id]['dividend'])) {
				$tickers[$id]['dividend']['sumDividend'] = 0.0;
				$tickers[$id]['dividend']['records'] = 0;
				$tickers[$id]['dividend']['avg'] = 0.0;
				$tickers[$id]['dividend']['predicted_payment'] = [];
				$tickers[$id]['dividend']['frequency'] = $frequency;
			}

			if ($cId > $lastYear) {
				$tickers[$id]['dividend'][
					$cId
				] = $tickerCalendar->getCashAmount();
				$tickers[$id]['dividend'][
					'sumDividend'
				] += $tickerCalendar->getCashAmount();
				$tickers[$id]['dividend']['records'] += 1;

				$owned = $tickers[$id]['instrument']->getOwnedQuantity();
				$tax = $tickers[$id]['tax']->getTaxRate();
				$predictedPayment =
					$owned *
					$tickerCalendar->getCashAmount() *
					$rateDollarEuro *
					(1 - $tax);
				$tickers[$id]['dividend']['predicted_payment'][
					$cId
				] = $predictedPayment;
			}
		}
	}

	protected function decorateInstruments(
		PaymentRepository $paymentRepository,
		Collection &$instruments,
		array &$pieInstruments,
		array &$tickers,
		float $pieAvgInvested,
		float $rateDollarEuro
	): array {
		$pieDividend = 0.0; // What is actually paid will be a computed on latest paydat so can be inaccurate. Trading212 does not split up payments by pie instruments :(
		$pieCurrentDividend = 0.0;
		$pieAvgDividend = 0.0;

		/**
		 * @var \App\Entity\Trading212PieInstrument $instrument
		 */
		foreach ($instruments as $instrument) {
			$ticker = $instrument->getTicker();
			if (!$ticker) {
				continue;
			}
			$tickerId = $instrument->getTicker()->getId();

			if ($instrument->getPriceAvgInvestedValue() == 0) {
				continue;
			}
			$instrumentTicker = $tickers[$tickerId];
			if (isset($instrumentTicker['dividend']['avg'])) {
				$instrumentTicker['dividend']['avg'] =
					$instrumentTicker['dividend']['sumDividend'] /
					$instrumentTicker['dividend']['records'];
			} else {
				$instrumentTicker['dividend']['avg'] = 0.0;
			}
			if (isset($instrumentTicker['calendars'])) {
				//dd(array_slice($instrumentTicker['dividend'], -6, null, true) );
				//array_slice($instrumentTicker['calendars'], -6);
				$cals = array_slice(
					$instrumentTicker['calendars'],
					-6,
					null,
					true
				);
				ksort($cals);
				$instrument->setCalendars($cals); // Last 6 months if data is available
				$instrument->setDividend($instrumentTicker['dividend']);
			}
			$tax = $instrumentTicker['tax']->getTaxRate();
			$instrument->setTaxRate($tax);
			$instrument->setExchangeRate($rateDollarEuro);

			$owned = $instrument->getOwnedQuantity();
			// Current
			$yearMonth = (int) date('Ym');
			$currentDividend = 0.0;
			if (isset($instrumentTicker['calendars'][$yearMonth])) {
				$currentDividend = $instrumentTicker['calendars'][
					$yearMonth
				]->getCashAmount();
			}
			$instrument->setCurrentDividendPerShare($currentDividend);

			$totalCurrentDividend =
				$currentDividend * $owned * (1 - $tax) * $rateDollarEuro;
			$instrument->setCurrentDividend($totalCurrentDividend);

			$instrument->setMonthlyYield(0.0);
			if ($instrument->getPriceAvgInvestedValue() > 0) {
				$monthlyYield =
					($totalCurrentDividend /
						$instrument->getPriceAvgInvestedValue()) *
					100;
				$instrument->setMonthlyYield($monthlyYield);
			}

			$currentYearlYield =
				(($ticker->getPayoutFrequency() * $totalCurrentDividend) /
					$instrument->getPriceAvgInvestedValue()) *
				100;
			$instrument->setCurrentYearlyYield($currentYearlYield);
			$pieCurrentDividend += $totalCurrentDividend;

			// Avg
			//$avgDividend = $calendarRepository->getAvgDividend($ticker);
			$avgDividend = $instrumentTicker['dividend']['avg'];
			$instrument->setAvgDividendPerShare($avgDividend);

			$avgExpectedDividend =
				$avgDividend * $owned * (1 - $tax) * $rateDollarEuro;
			$instrument->setAvgExpectedDividend($avgExpectedDividend);

			$avgYearlYield =
				(($ticker->getPayoutFrequency() * $avgExpectedDividend) /
					$instrument->getPriceAvgInvestedValue()) *
				100;
			$instrument->setAvgYearlyYield($avgYearlYield);
			$pieAvgDividend += $avgExpectedDividend;

			$pieShare = round(
				($instrument->getPriceAvgInvestedValue() / $pieAvgInvested) *
					100,
				2
			);
			$pieInstruments['labels'][] = $ticker->getFullname();
			$pieInstruments['data'][] = $pieShare;

			/**
			 * @var \App\Entity\Payment $payment
			 */
			$payment = $paymentRepository->getLastDividend(
				$ticker,
				$instrument->getCreatedAt()
			);
			if ($payment) {
				$amount = $payment->getAmount();
				$dividend = $payment->getDividend();
				$instrumentDividendPaid = ($dividend / $amount) * $owned;
				$instrument->setDividendPaid($instrumentDividendPaid);
				$pieDividend += $instrumentDividendPaid;
			}
		}

		return [
			'pieDividend' => $pieDividend,
			'pieCurrentDividend' => $pieCurrentDividend,
			'pieAvgDividend' => $pieAvgDividend,
		];
	}

	protected function createPieChart(
		ChartBuilderInterface $chartBuilder,
		array $pieInstruments
	): \Symfony\UX\Chartjs\Model\Chart {
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
		return $chartInstruments;
	}

	protected function createYieldChart(
		ChartBuilderInterface $chartBuilder,
		EntityManagerInterface $entityManager,
		Pie $pie,
		TranslatorInterface $translator
	): \Symfony\UX\Chartjs\Model\Chart {
		$yieldData = [];
		$sql = sprintf(
			'SELECT * FROM trading212_yield WHERE trading212_pie_id = %d',
			$pie->getTrading212PieId()
		);
		$data = $entityManager
			->getConnection()
			->prepare($sql)
			->executeQuery()
			->fetchAllAssociative();
		foreach ($data as $itemData) {
			$yieldData['labels'][] =
				$itemData['month'] . '-' . $itemData['year'];
			$yield = 0.0;
			if ($itemData['start_invested'] > 0) {
				$deltaGained =
					$itemData['end_gained'] - $itemData['start_gained'];
				$yield = round(
					($deltaGained / $itemData['start_invested']) * 100,
					2
				);
			}
			$yieldData['data'][] = $yield;
		}

		$chartYield = $chartBuilder->createChart(Chart::TYPE_BAR);
		$chartYield->setData([
			'labels' => $yieldData['labels'],
			'datasets' => [
				[
					'label' => 'Yield',
					'data' => $yieldData['data'],
				],
			],
		]);

		$chartYield->setOptions([
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
		return $chartYield;
	}

	protected function getChartData(
		Trading212PieMetaDataRepository $trading212PieMetaDataRepository,
		Pie $pie
	) {
		$labels = [];
		$allocationData = [];
		$valueData = [];
		$gained = [];
		$totalReturn = [];
		$breakEvenData = [];

		/**
		 * @var array<int, \App\Entity\Trading212PieMetaData> $data
		 */
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
			$totalReturn[] = $item->getGained() + $item->getPriceAvgValue();

			$breakEvenData[] =
				$item->getPriceAvgInvestedValue() -
				($item->getGained() + $item->getPriceAvgValue());
		}

		return [
			'allocationData' => $allocationData,
			'valueData' => $valueData,
			'gained' => $gained,
			'labels' => $labels,
			'totalReturn' => $totalReturn,
			'breakEvenData' => $breakEvenData,
		];
	}

	protected function createChart(
		array $data,
		Pie $pie,
		ChartBuilderInterface $chartBuilder,
		TranslatorInterface $translator
	): \Symfony\UX\Chartjs\Model\Chart {
		$colors = Colors::COLORS;

		$labels = $data['labels'];
		$allocationData = $data['allocationData'];
		$gained = $data['gained'];
		$totalReturn = $data['totalReturn'];
		$valueData = ['valueData'];

		$colors = Colors::COLORS;

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
			[
				'label' => $translator->trans('Total return'),
				'data' => $totalReturn,
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
				[
					'label' => $chartData[3]['label'],
					'backgroundColor' => $colors[3],
					'borderColor' => $colors,
					'data' => $chartData[3]['data'],
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

		return $chart;
	}

	protected function breakEvenChart(
		array $data,
		ChartBuilderInterface $chartBuilder,
		TranslatorInterface $translator
	) {
		$chart = $chartBuilder->createChart(Chart::TYPE_LINE);
		$chart->setData([
			'labels' => $data['labels'],
			'datasets' => [
				[
					'label' => $translator->trans('Break even'),
					'data' => $data['breakEvenData'],
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

	//Todo Refactor. Controller has become to fat and should be split
	// up into several parts.
	#[Route('/graph/{pie}', name: 'app_report_trading212_graph')]
	public function graph(
		Pie $pie,
		Trading212PieMetaDataRepository $trading212PieMetaDataRepository,
		DividendCalendarRepository $calendarRepository,
		PaymentRepository $paymentRepository,
		TickerRepository $tickerRepository,
		TranslatorInterface $translator,
		ExchangeRateInterface $exchangeRate,
		EntityManagerInterface $entityManager,
		ChartBuilderInterface $chartBuilder
	): Response {
		/**
		 * @var \App\Entity\Trading212PieMetaData $metaData
		 */
		$metaData = $trading212PieMetaDataRepository->findOneBy(
			['pie' => $pie],
			['createdAt' => 'DESC']
		);
		$stats = $this->calcStats($metaData);
		$instruments = $metaData->getTrading212PieInstruments();
		$pieAvgInvested = $metaData->getPriceAvgInvestedValue();
		$rates = $exchangeRate->getRates();
		$rateDollarEuro = 1 / $rates['USD'];

		$tickers = [];
		$priceProfitLoss = 0.0;
		// Get the tickers needed foor the rest
		foreach ($instruments as $instrument) {
			if (!$instrument->getTicker()) {
				$this->addFlash(
					'notice',
					$instrument->getTickerName() .
						' has not been assigned a ticker'
				);
				continue;
			}
			$priceProfitLoss +=
				$instrument->getPrice() - $instrument->getAvgPrice();
			$tickers[$instrument->getTicker()->getId()] = [
				'ticker' => $instrument->getTicker(),
				'instrument' => $instrument,
			];
		}

		// Get the taxrate for each ticker
		$tickerTaxes = $tickerRepository->getTaxForTickers(
			array_keys($tickers)
		);
		foreach ($tickerTaxes as $id => $tickerTax) {
			$tickers[$id]['tax'] = $tickerTax->getTax();
		}

		$this->decorateWithDividend(
			$calendarRepository,
			$tickers,
			$rateDollarEuro
		);

		$pieInstruments = [];
		$pieDividend = 0.0; // What is actually paid will be a computed on latest paydat so can be inaccurate. Trading212 does not split up payments by pie instruments :(
		$pieCurrentDividend = 0.0;
		$pieAvgDividend = 0.0;

		$dataInstruments = $this->decorateInstruments(
			$paymentRepository,
			$instruments,
			$pieInstruments,
			$tickers,
			$pieAvgInvested,
			$rateDollarEuro
		);

		if (!$pieInstruments) {
			return $this->render('trading212/report/no_graph.html.twig');
		}

		$chartInstruments = $this->createPieChart(
			$chartBuilder,
			$pieInstruments
		);

		$data = $this->getChartData($trading212PieMetaDataRepository, $pie);

		$chart = $this->createChart($data, $pie, $chartBuilder, $translator);

		$breakEvenChart = $this->breakEvenChart(
			$data,
			$chartBuilder,
			$translator
		);

		$chartYield = $this->createYieldChart(
			$chartBuilder,
			$entityManager,
			$pie,
			$translator
		);

		$date = new \DateTime('now');
		$date->modify('last day of this month');
		$paymentLimit = $date->format('Y-m-d');

		$pieDividend = $dataInstruments['pieDividend'];
		$pieCurrentDividend = $dataInstruments['pieCurrentDividend'];
		$pieAvgDividend = $dataInstruments['pieAvgDividend'];

		$monthsEstimatedBreakEven = $pieDividend > 0 ? ceil(
			($metaData->getPriceAvgInvestedValue() - $metaData->getGained()) /
				$pieDividend
		) : 0.0;
		$yearsEstimatedBreakEven = floor($monthsEstimatedBreakEven / 12);
		$periodEstimatedBreakEven['years'] = $yearsEstimatedBreakEven;
		$periodEstimatedBreakEven['months'] =
			$monthsEstimatedBreakEven - $yearsEstimatedBreakEven * 12;
		$pieYield =
			((12 * $pieDividend) / $metaData->getPriceAvgInvestedValue()) * 100;
		$pieYieldAvg =
			((12 * $pieAvgDividend) / $metaData->getPriceAvgInvestedValue()) *
			100;

		return $this->render(
			'trading212/report/graph.html.twig',
			array_merge(
				[
					'title' => 'Trading212Controller',
					'metaData' => $metaData,
					'monthsEstimatedBreakEven' => $monthsEstimatedBreakEven,
					'yearsEstimatedBreakEven' => $yearsEstimatedBreakEven,
					'periodEstimatedBreakEven' => $periodEstimatedBreakEven,
					'pie' => $pie,
					'pieDividend' => $pieDividend,
					'pieYield' => $pieYield,
					'pieCurrentDividend' => $pieCurrentDividend,
					'pieAvgDividend' => $pieAvgDividend,
					'pieYieldAvg' => $pieYieldAvg,
					'chart' => $chart,
					'breakEvenChart' => $breakEvenChart,
					'instruments' => $instruments,
					'chartInstruments' => $chartInstruments,
					'chartYield' => $chartYield,
					'paymentLimit' => $paymentLimit,
					'priceProfitLoss' => $priceProfitLoss,
				],
				$stats
			)
		);
	}

	#[Route('/summary', name: 'app_report_trading212_summary')]
	public function graphSummary(
		Trading212PieMetaDataRepository $trading212PieMetaDataRepository,
		ChartBuilderInterface $chartBuilder,
		TranslatorInterface $translator
	): Response {
		$data = $trading212PieMetaDataRepository->latest();
		$stats = $this->calcStats($data);

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
