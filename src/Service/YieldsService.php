<?php

namespace App\Service;

use App\Entity\Calendar;
use App\Entity\Pie;
use App\Entity\Constants;
use App\Entity\PositionYield;
use App\Repository\CalendarRepository;
use App\Repository\DividendMonthRepository;
use App\Repository\PositionRepository;
use App\Repository\TransactionRepository;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class YieldsService
{
	public function __construct(
		//private CacheInterface $pool,
		private Stopwatch $stopwatch,
		private PositionRepository $positionRepository,
		private TransactionRepository $transactionRepository,
		private DividendService $dividendService
	) {
	}
	public function yield(
		string $sort = 'symbol',
		string $sortDirection = 'ASC',
		?Pie $pie = null
	): array {
		$positionRepository = $this->positionRepository;
		$dividendService = $this->dividendService;

		$this->stopwatch->start('yield-data', 'pie-yield');

		$poolKey = 'positions_for_yield' . ($pie ? '_' . $pie->getId() : '');
		/* $yieldData = $this->pool->get($poolKey, function (
			ItemInterface $item
		) use ($positionRepository, $dividendService, $pie): PositionYield {
		*/
		$positionYield = new PositionYield();

		//$item->expiresAfter(3600);

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

		/*
		$this->stopwatch->start(
			'getting-sumallocated-from-database',
			'parsing'
		);
		$allocated = $positionRepository->getSumAllocated($pie);
		$this->stopwatch->stop('getting-sumallocated-from-database');
		*/
		$allocated = 0.0;

		$this->stopwatch->start('processing-file', 'parsing');

		$totalDividend = 0.0;
		$totalNetYearlyDividend = 0.0;
		$dividendYieldOnCost = 0.0;
		$totalAvgYield = 0.0;

		/**
		 * @var \App\Entity\Position $position
		 */
		foreach ($positions as $position) {
			$avgPrice =
				$pie && $report[$position->getId()]
					? $report[$position->getId()]['avgPrice']
					: $position->getPrice();
			$amount =
				$pie && $report[$position->getId()]
					? $report[$position->getId()]['amount']
					: $position->getAmount();
			$allocation =
				$pie && $report[$position->getId()]
					? $report[$position->getId()]['allocation']
					: $position->getAllocation();
			$ticker = $position->getTicker();

			$allocated += $allocation;

			$lastCash = 0;
			$lastDividendDate = null;

			$numPayoutsPerYear = $ticker->getDividendMonths()->count();
			$firstCalendarEntry = $ticker->getCalendars()->first();

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
				? $dividendService->getExchangeRate($firstCalendarEntry)
				: 0;

			if ($firstCalendarEntry) {
				$lastCash = $dividendService->getCashAmount($ticker); // $firstCalendarEntry->getCashAmount();
				$lastCashCurrency = $firstCalendarEntry
					->getCurrency()
					->getSign();
				$lastDividendDate = $firstCalendarEntry->getPaymentDate();

				$netTotalForwardYearlyPayout =
					$numPayoutsPerYear *
					$dividendService->getForwardNetDividend(
						$position->getTicker(),
						$amount
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
		];
	}
}
