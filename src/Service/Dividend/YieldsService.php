<?php

namespace App\Service\Dividend;

use App\Entity\Constants;
use App\Entity\Pie;
use App\Entity\PositionYield;
use App\Repository\PositionRepository;
use App\Repository\TransactionRepository;
use Symfony\Component\Stopwatch\Stopwatch;
use App\Decorator\Factory\AdjustedPositionDecoratorFactory;
use App\DataProvider\PositionDataProvider;
use App\DataProvider\CorporateActionDataProvider;
use App\DataProvider\DividendDataProvider;
use Doctrine\Common\Collections\ArrayCollection;
use App\Service\ExchangeRate\DividendExchangeRateResolverInterface;

class YieldsService
{
	public function __construct(
		//private CacheInterface $pool,
		private Stopwatch $stopwatch,
		private PositionRepository $positionRepository,
		private TransactionRepository $transactionRepository,
		private DividendService $dividendService,
		private DividendExchangeRateResolverInterface $dividendExchangeRateResolver,
		private PositionDataProvider $positionDataProvider,
		private CorporateActionDataProvider $corporateActionDataProvider,
		private DividendDataProvider $dividendDataProvider,
		private AdjustedPositionDecoratorFactory $adjustedPositionDecoratorFactory,
	) {
	}
	public function yield(
		string $sort = 'symbol',
		string $sortDirection = 'ASC',
		?Pie $pie = null
	): array {
		$positionRepository = $this->positionRepository;
		$this->stopwatch->start('yield-data', 'pie-yield');
		$positionYield = new PositionYield();
		$this->stopwatch->start('getting-positions-from-database', 'parsing');
		if ($pie) {
			$report = $this->transactionRepository->getSummaryByPie($pie);
			$positions = $positionRepository->getAllByIds(array_keys($report));
		} else {
			$positions = $positionRepository->getAllOpen($pie, null);
		}

		$this->stopwatch->stop('getting-positions-from-database');

		if ($pie && count($report) < 1) {
			return [];
		}

		$allocated = 0.0;

		$this->stopwatch->start('processing-file', 'parsing');

		$tickerList = array_map(function($position) {
			return $position->getTicker();
		}, $positions);

		$transactions = $this->positionDataProvider->load($positions);
		$actions = $this->corporateActionDataProvider->load($tickerList);
		$dividends = $this->dividendDataProvider->load($tickerList);

		$dividendService =  $this->dividendService->load(transactions: $transactions, corporateActions: $actions, dividends: $dividends);

		$totalDividend = 0.0;
		$totalNetYearlyDividend = 0.0;
		$totalNetYearlyDividendPerStock = 0.0;
		$totalNetMonthlyDividend = 0.0;
		$dividendYieldOnCost = 0.0;
		$totalAvgYield = 0.0;

		$this->adjustedPositionDecoratorFactory->load($transactions, $actions);

		/**
		 * @var \App\Entity\Position $position
		 */
		foreach ($positions as $position) {
			$avgPrice =
				$pie && $report[$position->getId()]
					? $report[$position->getId()]['avgPrice']
					: $position->getPrice();

			$amount = $position->getAmount();

			$positionDecorator = $this->adjustedPositionDecoratorFactory->decorate($position);
			$adjustedAmount = $positionDecorator->getAdjustedAmount();
			$adjustedAveragePrice = $positionDecorator->getAdjustedAveragePrice();

			$position->setAdjustedAmount($adjustedAmount);
			$position->setAdjustedAveragePrice($adjustedAveragePrice);

			$allocation =
				$pie && $report[$position->getId()]
					? $report[$position->getId()]['allocation']
					: $position->getAllocation();
			$ticker = $position->getTicker();

			$allocated += $allocation;

			$lastCash = 0;
			$lastDividendDate = null;

			$numPayoutsPerYear = $ticker->getDividendMonths()->count();
			$calendarList = new ArrayCollection($dividends[$position->getTicker()->getId()] ?? []);

			//$firstCalendarEntry = $ticker->getCalendars()->first();
			$firstCalendarEntry = $calendarList->first();

			$netTotalForwardYearlyPayout = 0;
			$netForwardYearlyPayout = 0;
			$dividendYield = 0;
			$netTotalPayoutPerPaydate = 0;
			$lastCash = 0;
			$lastCashCurrency = '$';
			$taxRate = $ticker->getTax()
				? $ticker->getTax()->getTaxRate() * 100
				: Constants::TAX;
			$exchangeRate = $firstCalendarEntry
				? $this->dividendExchangeRateResolver->getRateForCalendar($firstCalendarEntry)
				: 0;

			if ($firstCalendarEntry) {
//				$lastCash = $dividendService->getCashAmount($ticker);
				$lastCash = $dividendService->getCashAmount($ticker);

				$lastCashCurrency = $firstCalendarEntry
					->getCurrency()
					->getSign();
				$lastDividendDate = $firstCalendarEntry->getPaymentDate();

				$netTotalForwardYearlyPayout =
					$numPayoutsPerYear *
					$dividendService->getForwardNetDividend(
						$position->getTicker(),
						$position->getAdjustedAmount()
					);
				$netForwardYearlyPayout =
					$numPayoutsPerYear *
					$dividendService->getNetDividend(
						$position,
						$firstCalendarEntry
					);
				$dividendYield = $dividendService->getForwardNetDividendYield(
					$position,
					$position->getTicker(),
					$amount,
					$allocation
				);
				$netTotalPayoutPerPaydate = 0;
				if ($numPayoutsPerYear > 0) {
					$netTotalPayoutPerPaydate =
						$netTotalForwardYearlyPayout / $numPayoutsPerYear;
					$totalNetMonthlyDividend += $netTotalPayoutPerPaydate;
				}
			}
			$dividendPerYear = $numPayoutsPerYear * $lastCash;

			$tickerLabel = $ticker->getSymbol();
			$positionYield->labels[$tickerLabel] = sprintf(
				'%s (%s)',
				substr(
					addslashes(
						str_replace(
							["'", '"'],
							['', ''],
							$ticker->getFullname()
						)
					),
					0,
					8
				),
				$ticker->getSymbol()
			);
			$positionYield->data[$tickerLabel] = $dividendYield;

			$orderKey['yield'] =
				str_pad(
					(string) ($dividendYield * 100),
					10,
					'0',
					STR_PAD_LEFT
				) . $ticker->getSymbol();
			$orderKey['dividend'] =
				str_pad(
					(string) ($dividendPerYear * 100),
					10,
					'0',
					STR_PAD_LEFT
				) . $ticker->getSymbol();
			$orderKey['symbol'] = $ticker->getSymbol();

			$positionYield->dataSource['symbol'][
				$orderKey['symbol']
			] = $positionYield->dataSource['dividend'][
				$orderKey['dividend']
			] = $positionYield->dataSource['yield'][$orderKey['yield']] = [
				'ticker' => $ticker->getSymbol(),
				'tickerId' => $ticker->getId(),
				'position' => $position,
				'label' => $ticker->getFullname(),
				'yield' => $dividendYield,
				'payout' => $dividendPerYear,
				'allocation' => $allocation,
				'netTotalPayoutPerPaydate' => $netTotalPayoutPerPaydate,
				'netForwardYearlyPayout' => $netForwardYearlyPayout,
				'netTotalForwardYearlyPayout' => $netTotalForwardYearlyPayout,
				'avgPrice' => $avgPrice,
				'lastDividend' => $lastCash,
				'lastDividendCurrency' => $lastCashCurrency,
				'lastDividendDate' => $lastDividendDate,
				'numPayoutsPerYear' => $numPayoutsPerYear,
				'amount' => $amount,
				'taxRate' => $taxRate,
				'exchangeRate' => $exchangeRate,
			];
			$totalNetYearlyDividend += $netTotalForwardYearlyPayout;
			$positionYield->sumAvgPrice += $avgPrice;
			$positionYield->sumDividends += $dividendPerYear;
			$totalDividend += $dividendPerYear * $amount;
			$totalNetYearlyDividendPerStock += $netForwardYearlyPayout;
			$this->stopwatch->lap('processing-file');
		}
		$this->stopwatch->stop('processing-file');

		if ($positionYield->sumAvgPrice) {
			$totalAvgYield =
				($positionYield->sumDividends / $positionYield->sumAvgPrice) *
				100;
		}
		if ($allocated) {
			$dividendYieldOnCost = ($totalNetYearlyDividend / $allocated) * 100;
		}
		$yieldData = $positionYield;

		//return $positionYield;
		//});

		ksort($yieldData->labels);
		ksort($yieldData->data);

		match (strtolower($sortDirection)) {
			'desc' => krsort($yieldData->dataSource[$sort]),
			'asc' => ksort($yieldData->dataSource[$sort]),
			default => ksort($yieldData->dataSource[$sort]),
		};

		//$this->pool->delete($poolKey);

		return [
			'data' => array_values($yieldData->data),
			'labels' => array_values($yieldData->labels),
			'datasource' => $yieldData->dataSource[$sort],
			'totalAvgYield' => $totalAvgYield,
			'dividendYieldOnCost' => $dividendYieldOnCost,
			'allocated' => $allocated,
			'totalDividend' => $totalDividend,
			'totalNetYearlyDividend' => $totalNetYearlyDividend,
			'totalNetMonthlyDividend' => $totalNetMonthlyDividend,
			'totalNetYearlyDividendPerStock' => $totalNetYearlyDividendPerStock,
		];
	}
}
