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

		$pieInstruments = [];
		$pieDividend = 0.0; // What is actually paid will be a computed on latest paydat so can be inaccurate. Trading212 does not split up payments by pie instruments :(
		$pieCurrentDividend = 0.0;
		$pieAvgDividend = 0.0;

		/*
		$dataInstruments = $this->decorateInstruments(
			$paymentRepository,
			$instruments,
			$pieInstruments,
			$tickers,
			$pieAvgInvested,
			$rateDollarEuro
		);
		*/

		// @todo: need to get Collection $payments
		//$payments = new ArrayCollection([]);
		$payments = new ArrayCollection($this->paymentRepository->getLastDividends($tickers));
dd($payments);
		$dataInstruments = $this->instrumentDecorator->instruments(
			$pieAvgInvested,
			$instruments,
			$payments,
			$tickers,
			$rateDollarEuro
		);


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

		$pieDividend = $dataInstruments['pieDividend'];
		$pieCurrentDividend = $dataInstruments['pieCurrentDividend'];
		$pieAvgDividend = $dataInstruments['pieAvgDividend'];

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
			$tax = $instrumentTicker['tax']->getTax()->getTaxRate();
			$instrument->setTaxRate($tax);
			$instrument->setExchangeRate($rateDollarEuro);

			$owned = $instrument->getOwnedQuantity();
			// Current
			$yearMonth = (int) date('Ym');
			$currentDividend = 0.0;
			if (isset($instrumentTicker['calendars'][$yearMonth])) {
				$currentDividend = $instrumentTicker['calendars'][$yearMonth]->getAdjustedCashAmount();
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
}
