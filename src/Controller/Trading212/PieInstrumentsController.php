<?php

namespace App\Controller\Trading212;

use App\Entity\Pie;
use App\Repository\DividendCalendarRepository;
use App\Repository\PaymentRepository;
use App\Repository\TickerRepository;
use App\Service\ExchangeRate\ExchangeRateInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Common\Collections\Collection;
use App\Decorator\Factory\AdjustedDividendDecoratorFactory;

use App\Service\Trading212\CalcStatsService;
use App\DataProvider\PieDataProvider;
use App\Service\Trading212\Factory\ChartBuilderFactory;
use App\Decorator\DividendDecorator;
use App\Decorator\TickerTaxDecorator;
use App\Decorator\InstrumentDecorator;
use Doctrine\Common\Collections\ArrayCollection;

#[
	Route(
		path: '/{_locale<%app.supported_locales%>}/dashboard/trading212/report'
	)
]
class PieInstrumentsController extends AbstractController
{
	private const MONTHS_IN_YEAR = 12;

	public function __construct(
		private CalcStatsService $calcStatsService,
		private readonly ChartBuilderFactory $chartBuilderFactory,
		private readonly PieDataProvider $pieDataProvider,
		private readonly TickerTaxDecorator $taxDecorator,
		private readonly DividendDecorator $dividendDecorator,
		private readonly InstrumentDecorator $instrumentDecorator,
		private readonly TickerRepository $tickerRepository,
		private readonly PaymentRepository $paymentRepository,
	) {}

	//Todo Refactor. Controller has become to fat and should be split
	// up into several parts.
	#[Route('/pie-instruments/{pie}', name: 'app_report_trading212_pie_instruments')]
	public function index(
		#[MapEntity] Pie $pie,
		EntityManagerInterface $entityManager,
	): Response {

		// 1️⃣ Load all data for the pie
		$pieDataDto = $this->pieDataProvider->load($pie);

		// 2️⃣ Stats & basic values
		$metaData = $pieDataDto->metaData->first();
		$stats = $this->calcStatsService->calc($metaData);
		$instruments = $pieDataDto->instruments;
		$corporateActions = $pieDataDto->corporateActions;
		$dividends = $pieDataDto->dividends;
		$pieAvgInvested = $metaData->getPriceAvgInvestedValue();


		// 3️⃣ Prepare tickers map
		$tickers = [];
		$priceProfitLoss = 0.0;

		/**
		 * Get the tickers needed for the rest
		 * If a instrument has no ticker attached yet, notify user
		 */
		foreach ($instruments as $instrument) {
			if (!$instrument->getTicker()) {
				$this->addFlash(
					'notice',
					$instrument->getTickerName() .
						' has not been assigned a ticker'
				);
				continue;
			}
			/**
			 * Profit/loss based on price action
			 *
			 * @var \App\Entity\Ticker $ticker
			 */
			$ticker = $instrument->getTicker();
			$priceProfitLoss +=
				$instrument->getPrice() - $instrument->getAvgPrice();

			$tickers[$ticker->getId()] = [
				'ticker' => $instrument->getTicker(),
				'instrument' => $instrument,
				'adjustedDividend' => [],
			];
		}

		// 4️⃣ Decorate dividends (adjust dividend where needed.)
		$this->dividendDecorator->decorate($tickers, $dividends, $corporateActions);

		// 5️⃣ Taxes (hated taxes) for each ticker some have 15% others 0% and some 30% tax rate
		$tickerIds = array_keys($tickers);
		$taxes = $this->tickerRepository->getTaxForTickers($tickerIds);
		$this->taxDecorator->decorate($tickers, $taxes);

		// 6️⃣ Further decorations (break‑even, current dividend …)
		$rateDollarEuro = 1 / $this->pieDataProvider->getExchangeRates()['USD'];
		$this->instrumentDecorator->dividend($dividends, $tickers, $rateDollarEuro);

		$currentMonth = date('Ym');
		$totalMonthlyDividend = 0.0;
		foreach ($tickers as $id => $item) {
			if (isset($item['dividend'])) {
				$lastDividend = 0.0;
				foreach (
					$item['dividend']['predicted_payment_monthly']
					as $month => $predictedMonthlyDividend
				) {
					if ($month > $currentMonth) {
						continue;
					}
					$lastDividend = $predictedMonthlyDividend;
				}
				$totalMonthlyDividend += $lastDividend;
			}
		}
		$yearlyDividendPercentage =
			$metaData->getPriceAvgInvestedValue() > 0
			? ($totalMonthlyDividend * static::MONTHS_IN_YEAR) /
			$metaData->getPriceAvgInvestedValue()
			: 0;
		$stats['yearlyDividendPercentage'] = $yearlyDividendPercentage * 100;
		$stats['monthlyDividend'] = $totalMonthlyDividend;
		$stats['yearlyDividend'] = $totalMonthlyDividend * static::MONTHS_IN_YEAR;

		$payments = new ArrayCollection($this->paymentRepository->getLastDividends($tickers));
		$dataInstruments = $this->instrumentDecorator->instruments(
			$pieAvgInvested,
			$instruments,
			$payments,
			$tickers,
			$rateDollarEuro
		);

		$pieInstruments = $dataInstruments['pieInstruments'] ?? [];
		$pieDividend = $dataInstruments['pieDividend'] ?? 0.0;
		$pieCurrentDividend = $dataInstruments['pieCurrentDividend'] ?? 0.0;
		$pieAvgDividend = $dataInstruments['pieAvgDividend'] ?? 0.0;


		if (!$pieInstruments) {
			return $this->render('trading212/report/no_graph.html.twig');
		}

		$chartInstruments = $this->chartBuilderFactory->buildInstrumentsChart($pieInstruments);

		$data = $this->getChartData($pieDataDto->metaData);

		$chart = $this->chartBuilderFactory->buildMainChart($data, $pie);

		$breakEvenChart = $this->chartBuilderFactory->buildBreakEvenChart(
			$data,
		);

		$sql = sprintf(
			'SELECT * FROM trading212_yield WHERE trading212_pie_id = %d',
			$pie->getTrading212PieId()
		);
		$data = $entityManager
			->getConnection()
			->prepare($sql)
			->executeQuery()
			->fetchAllAssociative();

		$chartYield = $this->chartBuilderFactory->buildYieldChart(
			$data,
			$pie
		);

		$date = new \DateTime('now');
		$date->modify('last day of this month');
		$paymentLimit = $date->format('Y-m-d');

		$monthsEstimatedBreakEven =
			$pieDividend > 0
			? ceil(
				($metaData->getPriceAvgInvestedValue() -
					$metaData->getGained()) /
					$pieDividend
			)
			: 0.0;
		$yearsEstimatedBreakEven = floor($monthsEstimatedBreakEven / 12);
		$periodEstimatedBreakEven['years'] = $yearsEstimatedBreakEven;
		$periodEstimatedBreakEven['months'] =
			$monthsEstimatedBreakEven - $yearsEstimatedBreakEven * 12;
		$pieYield =
			((static::MONTHS_IN_YEAR * $pieDividend) / $metaData->getPriceAvgInvestedValue()) * 100;
		$pieYieldAvg =
			((static::MONTHS_IN_YEAR * $pieAvgDividend) / $metaData->getPriceAvgInvestedValue()) *
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

	private function getChartData(
		Collection $metaData
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
		$data = $metaData->toArray();

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
}
