<?php

namespace App\Service;

use App\Repository\BranchRepository;
use App\Repository\PositionRepository;
use App\Service\Summary;

class Allocation
{
    public function allocation(
        BranchRepository $branchRepository,
        PositionRepository $positionRepository
    ): array {
        $allocated = $positionRepository->getSumAllocated();
        $branches = $branchRepository->findAll();

        $data = [];
        foreach ($branches as $branch) {
            $item = [
                'industry' => $branch->getLabel(),
                'allocation' => 0,
                'allocationPercentage' => 0,
                'targetAllocationPercentage' => $branch->getAssetAllocation() / 100,
                'dividend' => 0,
                'tickers' => 0,
            ];

            $tickers = $branch->getTickers();
            foreach ($tickers as $tickers) {
                $item['tickers'] += 1;
                foreach ($tickers->getPositions() as $position) {
                    $item['allocation'] += $position->getAllocation();
                    $item['dividend'] += $position->getDividend();
                }
            }
            $item['allocationPercentage'] = 0;
            if ($allocated > 0) {
                $item['allocationPercentage'] = ((int) $item['allocation'] / (int) $allocated) * 100;
            }
            $data[$item['allocation']] = $item;
        }

        krsort($data);

        return $data;
    }

    public function sector(
        PositionRepository $positionRepository,
        BranchRepository $branchRepository,
        Summary $summary
    ): array {
        [$numActivePosition, $numTickers, $profit, $totalDividend, $allocated] = $summary->getSummary();

        $sectors = $branchRepository->getAllocationPerSector();
        $totalAllocated = $positionRepository->getSumAllocated();

        $allocationData = $positionRepository->getAllocationDataPerSector();
        $labels = [];
        $data = [];
        foreach ($allocationData as $allocationItem) {
            $labels[] = $allocationItem['industry'];
            $allocation = $allocationItem['allocation'] / 100;
            $data[] = round(($allocation / $totalAllocated) * 100, 2);
        }

        return  [
            'data' => json_encode($data),
            'labels' => json_encode($labels),
            'sectors' => $sectors,
            'numActivePosition' => $numActivePosition,
            'numPosition' => $numActivePosition,
            'numTickers' => $numTickers,
            'profit' => $profit,
            'totalDividend' => $totalDividend,
            'totalInvested' => $allocated
        ];
    }

    public function position(
        PositionRepository $positionRepository,
        Summary $summary
    ): array {
        [$numActivePosition, $numTickers, $profit, $totalDividend, $allocated] = $summary->getSummary();

        $totalAllocated = $positionRepository->getSumAllocated();

        $allocationData = $positionRepository->getAllocationDataPerPosition();
        $labels = [];
        $data = [];
        foreach ($allocationData as $allocationItem) {
            $labels[] = $allocationItem['ticker'];
            $allocation = $allocationItem['allocation'] / 100;
            $data[] = round(($allocation / $totalAllocated) * 100, 2);
        }

        return [
            'data' => json_encode($data),
            'labels' => json_encode($labels),
            'numActivePosition' => $numActivePosition,
            'numPosition' => $numActivePosition,
            'numTickers' => $numTickers,
            'profit' => $profit,
            'totalDividend' => $totalDividend,
            'totalInvested' => $allocated,
        ];
    }
}
