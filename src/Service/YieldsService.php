<?php

namespace App\Service;

use App\Entity\Pie;
use App\Entity\Constants;
use App\Repository\PositionRepository;

class YieldsService
{
	public function yield(
		PositionRepository $positionRepository,
		DividendService $dividendService,
		string $sort = 'symbol',
		string $sortDirection = 'ASC',
		?Pie $pie = null
	): array {
		$labels = [];
		$data = [];
		$dataSource = [];
		$sumDividends = 0.0;
		$sumAvgPrice = 0.0;
		$totalDividend = 0.0;
		$orderKey = 0;
		$totalNetYearlyDividend = 0.0;

		$positions = $positionRepository->getAllOpen($pie, null);
		$allocated = $positionRepository->getSumAllocated($pie);

		foreach ($positions as $position) {
			if ($position->isIgnoreForDividend()) {
				continue;
			}

			$ticker = $position->getTicker();
			$avgPrice = $position->getPrice();
			$amount = $position->getAmount();
			$allocation = $position->getAllocation();
			$scheduleCalendar = $ticker->getDividendMonths();
			$numPayoutsPerYear = count($scheduleCalendar);
			$lastCash = 0;
			$lastDividendDate = null;
			$payCalendars = $ticker->getCalendars();
			$firstCalendarEntry = $payCalendars->first();

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
					$dividendService->getForwardNetDividend($position);
				$netForwardYearlyPayout =
					$numPayoutsPerYear *
					$dividendService->getNetDividend(
						$position,
						$firstCalendarEntry
					);
				$dividendYield = $dividendService->getForwardNetDividendYield(
					$position
				);
				$netTotalPayoutPerPaydate = 0;
				if ($numPayoutsPerYear > 0) {
					$netTotalPayoutPerPaydate =
						$netTotalForwardYearlyPayout / $numPayoutsPerYear;
				}
			}
			$dividendPerYear = $numPayoutsPerYear * $lastCash;

			$tickerLabel = $ticker->getSymbol();
			$labels[$tickerLabel] = sprintf(
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
			$data[$tickerLabel] = $dividendYield;

			$orderKey = match ($sort) {
				'yield' => str_pad(
					(string) ($dividendYield * 100),
					10,
					'0',
					STR_PAD_LEFT
				) . $ticker->getSymbol(),
				'dividend' => str_pad(
					(string) ($dividendPerYear * 100),
					10,
					'0',
					STR_PAD_LEFT
				) . $ticker->getSymbol(),
				'symbol' => $ticker->getSymbol(),
				default => $ticker->getSymbol(),
			};

			$dataSource[$orderKey] = [
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
			$sumAvgPrice += $avgPrice;
			$sumDividends += $dividendPerYear;
			$totalDividend += $dividendPerYear * $amount;
		}
		ksort($labels);
		ksort($data);

		match (strtolower($sortDirection)) {
			'desc' => krsort($dataSource),
			'asc' => ksort($dataSource),
			default => ksort($dataSource),
		};

		$totalAvgYield = 0.0;
		$dividendYieldOnCost = 0.0;
		if ($sumAvgPrice) {
			$totalAvgYield = ($sumDividends / $sumAvgPrice) * 100;
		}
		if ($allocated) {
			$dividendYieldOnCost = ($totalNetYearlyDividend / $allocated) * 100;
		}

		return [
			'data' => array_values($data),
			'labels' => array_values($labels),
			'datasource' => $dataSource,
			'totalAvgYield' => $totalAvgYield,
			'dividendYieldOnCost' => $dividendYieldOnCost,
			'allocated' => $allocated,
			'totalDividend' => $totalDividend,
			'totalNetYearlyDividend' => $totalNetYearlyDividend,
		];
	}
}
