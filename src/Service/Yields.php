<?php

namespace App\Service;

use App\Repository\PositionRepository;
use App\Repository\TickerRepository;

class Yields
{
    public function yield(
        TickerRepository $tickerRepository,
        PositionRepository $positionRepository,
        string $orderBy = 'ticker',
        float $exchangeRate = 1.19,
        float $tax = 0.15
    ): array {
        $labels = [];
        $data = [];
        $dataSource = [];

        $sumDividends = 0;
        $sumAvgPrice = 0;
        $tickers = $tickerRepository->getActiveForDividendYield();
        $allocated = $positionRepository->getSumAllocated();
        $totalDividend = 0;
        $orderKey = 0;
        foreach ($tickers as $ticker) {
            $positions = $ticker->getPositions();
            $position = $positions[0];
            $avgPrice = $position->getPrice();

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

            $dividendYield = round(((($dividendPerYear * (1 - $tax)) / $exchangeRate) / $avgPrice) * 100, 2);
            $labels[] = sprintf("%s (%s)", substr(addslashes($ticker->getFullname()), 0, 8), $ticker->getTicker());
            $data[] = $dividendYield;

            if ($orderBy === 'yield') {
                $orderKey = str_pad($dividendYield * 100, 10, '0', STR_PAD_LEFT) . $ticker->getTicker();
            }
            if ($orderBy === 'dividend') {
                $orderKey = str_pad($dividendPerYear * 100, 10, '0', STR_PAD_LEFT) . $ticker->getTicker();
            }
            if ($orderBy === 'ticker') {
                $orderKey += 1;
            }
            $dataSource[$orderKey] = [
                'ticker' => $ticker->getTicker(),
                'label' => $ticker->getFullname(),
                'yield' => $dividendYield,
                'payout' => $dividendPerYear,
                'avgPrice' => $avgPrice,
                'lastDividend' => $lastCash,
                'lastDividendDate' => $lastDividendDate,
            ];

            $sumAvgPrice += $avgPrice;
            $sumDividends += $dividendPerYear;
            $totalDividend += ($dividendPerYear * $position->getAmount()) / 10000;
        }

        if ($orderBy === 'yield' || $orderBy === 'dividend') {
            krsort($dataSource);
        }
        $totalAvgYield = ($sumDividends / $sumAvgPrice) * 100;
        $dividendYieldOnCost = ($totalDividend / $allocated) * 100;

        return [
            'data' => json_encode($data),
            'labels' => json_encode($labels),
            'datasource' => $dataSource,
            'totalAvgYield' => $totalAvgYield,
            'dividendYieldOnCost' => $dividendYieldOnCost,
            'allocated' => $allocated,
            'totalDividend' => $totalDividend,
        ];
    }
}
