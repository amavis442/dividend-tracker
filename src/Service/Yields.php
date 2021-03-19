<?php

namespace App\Service;

use App\Repository\PositionRepository;

class Yields
{
    function yield (
        PositionRepository $positionRepository,
        string $orderBy = 'ticker',
        float $exchangeRate = 1.19,
        float $tax = 0.15,
        int $pieId = null
    ): array{
        $labels = [];
        $data = [];
        $dataSource = [];
        $sumDividends = 0.0;
        $sumAvgPrice = 0.0;
        $totalDividend = 0.0;
        $orderKey = 0;
        $totalNetYearlyDividend = 0.0;

        $positions = $positionRepository->getAllOpen($pieId);
        $allocated = $positionRepository->getSumAllocated($pieId);

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
            if ($firstCalendarEntry) {
                $lastCash = $firstCalendarEntry->getCashAmount();
                $lastDividendDate = $firstCalendarEntry->getPaymentDate();
            }
            $dividendPerYear = $numPayoutsPerYear * $lastCash;

            $netForwardYearlyPayout = ($dividendPerYear * (1 - $tax)) / $exchangeRate;
            $netTotalForwardYearlyPayout = $amount * $netForwardYearlyPayout;
            $dividendYield = round(((($dividendPerYear * (1 - $tax)) / $exchangeRate) / $avgPrice) * 100, 2);
            $tickerLabel = $ticker->getTicker();
            $labels[$tickerLabel] = sprintf("%s (%s)", substr(addslashes(str_replace(["'",'"'],["",""],$ticker->getFullname())), 0, 8), $ticker->getTicker());
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
                'label' => $ticker->getFullname(),
                'yield' => $dividendYield,
                'payout' => $dividendPerYear,
                'allocation' => $allocation,
                'netForwardYearlyPayout' => $netForwardYearlyPayout,
                'netTotalForwardYearlyPayout' => $netTotalForwardYearlyPayout,
                'avgPrice' => $avgPrice,
                'lastDividend' => $lastCash,
                'lastDividendDate' => $lastDividendDate,
                'numPayoutsPerYear' => $numPayoutsPerYear,
                'amount' => $amount,
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
