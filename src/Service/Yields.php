<?php

namespace App\Service;

use App\Repository\PositionRepository;
use App\Repository\TickerRepository;

class Yields
{
    function yield (
        TickerRepository $tickerRepository,
        PositionRepository $positionRepository,
        string $orderBy = 'ticker',
        float $exchangeRate = 1.19,
        float $tax = 0.15
    ): array{
        $labels = [];
        $data = [];
        $dataSource = [];
        $sumDividends = 0.0;
        $sumAvgPrice = 0.0;
        $totalDividend = 0.0;
        $orderKey = 0;
        $totalNetYearlyDividend = 0.0;

        $positions = $positionRepository->getAllOpen();
        $allocated = $positionRepository->getSumAllocated();

        foreach ($positions as $position) {
            $ticker = $position->getTicker();
            $avgPrice = $position->getPrice() / 1000;
            $amount = $position->getAmount() / 10000000;

            $scheduleCalendar = $ticker->getDividendMonths();
            $numPayoutsPerYear = count($scheduleCalendar);
            $lastCash = 0;
            $lastDividendDate = null;
            $payCalendars = $ticker->getCalendars();
            $firstCalendarEntry = $payCalendars->first();
            if ($firstCalendarEntry) {
                $lastCash = $firstCalendarEntry->getCashAmount() / 1000;
                $lastDividendDate = $firstCalendarEntry->getPaymentDate();
            }
            $dividendPerYear = $numPayoutsPerYear * $lastCash;

            $netForwardYearlyPayout = ($dividendPerYear * (1 - $tax)) / $exchangeRate;
            $netTotalForwardYearlyPayout = $amount * $netForwardYearlyPayout;
            $dividendYield = round(((($dividendPerYear * (1 - $tax)) / $exchangeRate) / $avgPrice) * 100, 2);
            $tickerLabel = $ticker->getTicker();

            $labels[$tickerLabel] = sprintf("%s (%s)", substr(addslashes($ticker->getFullname()), 0, 8), $ticker->getTicker());
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
        //dump(array_values($labels));//, array_values($data));
        ksort($labels);
        ksort($data);
        //dd(array_values($labels));//, array_values($data));

        if ($orderBy === 'yield' || $orderBy === 'dividend') {
            krsort($dataSource);
        } else {
            ksort($dataSource);
        }
        $totalAvgYield = ($sumDividends / $sumAvgPrice) * 100;
        $dividendYieldOnCost = ($totalNetYearlyDividend / $allocated) * 100;

        return [
            'data' => json_encode(array_values($data)),
            'labels' => json_encode(array_values($labels)),
            'datasource' => $dataSource,
            'totalAvgYield' => $totalAvgYield,
            'dividendYieldOnCost' => $dividendYieldOnCost,
            'allocated' => $allocated,
            'totalDividend' => $totalDividend,
            'totalNetYearlyDividend' => $totalNetYearlyDividend,
        ];
    }
}
