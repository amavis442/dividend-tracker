<?php

namespace App\Service;

use App\Entity\Constants;
use App\Repository\PositionRepository;

class YieldsService
{
    public function yield(
        PositionRepository $positionRepository,
        DividendService $dividendService,
        string $orderBy = 'ticker',
        int $pieId = null
    ): array {
        $labels = [];
        $data = [];
        $dataSource = [];
        $sumDividends = 0.0;
        $sumAvgPrice = 0.0;
        $totalDividend = 0.0;
        $orderKey = 0;
        $totalNetYearlyDividend = 0.0;

        $positions = $positionRepository->getAllOpen($pieId, null, true);
        $allocated = $positionRepository->getSumAllocated($pieId);

        foreach ($positions as $position) {
            if ($position->isIgnoreForDividend()) continue;

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
            $taxRate = $ticker->getTax() ? $ticker->getTax()->getTaxRate() * 100 : Constants::TAX;
            $exchangeRate = $firstCalendarEntry ? $dividendService->getExchangeRate($firstCalendarEntry) : 0;


            if ($firstCalendarEntry) {
                $lastCash = $dividendService->getCashAmount($ticker); // $firstCalendarEntry->getCashAmount();
                $lastCashCurrency = $firstCalendarEntry->getCurrency()->getSign();
                $lastDividendDate = $firstCalendarEntry->getPaymentDate();

                $netTotalForwardYearlyPayout = $numPayoutsPerYear * $dividendService->getForwardNetDividend($position);
                $netForwardYearlyPayout = $numPayoutsPerYear * $dividendService->getNetDividend($position, $firstCalendarEntry);
                $dividendYield = $dividendService->getForwardNetDividendYield($position);
                $netTotalPayoutPerPaydate = 0;
                if ($numPayoutsPerYear > 0) {
                    $netTotalPayoutPerPaydate = $netTotalForwardYearlyPayout / $numPayoutsPerYear;
                }
            }
            $dividendPerYear = $numPayoutsPerYear * $lastCash;

            $tickerLabel = $ticker->getTicker();
            $labels[$tickerLabel] = sprintf("%s (%s)", substr(addslashes(str_replace(["'", '"'], ["", ""], $ticker->getFullname())), 0, 8), $ticker->getTicker());
            $data[$tickerLabel] = $dividendYield;

            if ($orderBy === 'yield') {
                $orderKey = str_pad($dividendYield * 100, 10, '0', STR_PAD_LEFT) . $ticker->getTicker();
            }
            if ($orderBy === 'dividend') {
                $orderKey = str_pad($dividendPerYear * 100, 10, '0', STR_PAD_LEFT) . $ticker->getTicker();
            }
            if ($orderBy === 'ticker') {
                $orderKey = $ticker->getTicker();
            }
            $dataSource[$orderKey] = [
                'ticker' => $ticker->getTicker(),
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
                'exchangeRate' => $exchangeRate
            ];
            $totalNetYearlyDividend += $netTotalForwardYearlyPayout;
            $sumAvgPrice += $avgPrice;
            $sumDividends += $dividendPerYear;
            $totalDividend += $dividendPerYear * $amount;
        }
        ksort($labels);
        ksort($data);

        if ($orderBy === 'yield' || $orderBy === 'dividend') {
            krsort($dataSource);
        } else {
            ksort($dataSource);
        }

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
