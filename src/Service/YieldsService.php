<?php

namespace App\Service;

use App\Entity\Pie;
use App\Entity\Constants;
use App\Entity\PositionYield;
use App\Repository\PositionRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class YieldsService
{
	public function __construct(private CacheInterface $pool)
	{
	}
	public function yield(
		PositionRepository $positionRepository,
		DividendService $dividendService,
		string $sort = 'symbol',
		string $sortDirection = 'ASC',
		?Pie $pie = null
	): array {
		$yieldData = $this->pool->get('positions_for_yield'.($pie ? '_'.$pie->getId(): ''), function (
			ItemInterface $item
		) use ($positionRepository, $dividendService, $pie): PositionYield {
			$positionYield = new PositionYield();

			$item->expiresAfter(3600);

			$positions = $positionRepository->getAllOpen($pie, null);

			//$positions = $positionRepository->getAllOpen($pie, null);
			$positionYield->allocated = $positionRepository->getSumAllocated($pie);

			/**
			 * @var \App\Entity\Position $position
			 */
			foreach ($positions as $position) {
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

				$orderKey['yield'] = str_pad(
					(string) ($dividendYield * 100),
					10,
					'0',
					STR_PAD_LEFT
				) . $ticker->getSymbol();
				$orderKey['dividend'] = str_pad(
					(string) ($dividendPerYear * 100),
					10,
					'0',
					STR_PAD_LEFT
				) . $ticker->getSymbol();
				$orderKey['symbol'] = $ticker->getSymbol();


				$positionYield->dataSource['symbol'][$orderKey['symbol']]  =
				$positionYield->dataSource['dividend'][$orderKey['dividend']] =
				$positionYield->dataSource['yield'][$orderKey['yield']]
				= [
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
				$positionYield->totalNetYearlyDividend += $netTotalForwardYearlyPayout;
				$positionYield->sumAvgPrice += $avgPrice;
				$positionYield->sumDividends += $dividendPerYear;
				$positionYield->totalDividend += $dividendPerYear * $amount;
			}

			if ($positionYield->sumAvgPrice) {
				$positionYield->totalAvgYield = ($positionYield->sumDividends / $positionYield->sumAvgPrice) * 100;
			}
			if ($positionYield->allocated) {
				$positionYield->dividendYieldOnCost = ($positionYield->totalNetYearlyDividend / $positionYield->allocated) * 100;
			}

			return $positionYield;
		});

		ksort($yieldData->labels);
		ksort($yieldData->data);

		match (strtolower($sortDirection)) {
			'desc' => krsort($yieldData->dataSource[$sort]),
			'asc' => ksort($yieldData->dataSource[$sort]),
			default => ksort($yieldData->dataSource[$sort]),
		};

		return [
			'data' => array_values($yieldData->data),
			'labels' => array_values($yieldData->labels),
			'datasource' => $yieldData->dataSource[$sort],
			'totalAvgYield' => $yieldData->totalAvgYield,
			'dividendYieldOnCost' => $yieldData->dividendYieldOnCost,
			'allocated' => $yieldData->allocated,
			'totalDividend' => $yieldData->totalDividend,
			'totalNetYearlyDividend' => $yieldData->totalNetYearlyDividend,
		];
	}
}
