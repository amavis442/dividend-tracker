<?php

namespace App\Service;

use App\Repository\BranchRepository;
use App\Repository\PositionRepository;
use App\Service\Summary;

class Allocation
{
    public function allocation(
        PositionRepository $positionRepository
    ): array {
        $allocated = $positionRepository->getSumAllocated();
        $positions = $positionRepository->getAllOpen();

        $data = [];
        $items = [];
        $totalAllocation = 0.0;
        foreach ($positions as $position) {
            if (!isset($items[$position->getTicker()->getBranch()->getLabel()])) {
                $items[$position->getTicker()->getBranch()->getLabel()] = 0.0;
            }
            $allocation = $position->getAllocation() / 1000;
            $items[$position->getTicker()->getBranch()->getLabel()] += $allocation;
            $totalAllocation += $allocation;
        }
        krsort($items);
    
        foreach ($items as $branch => $allocation) {
            $allocationPercentage = ($allocation / $totalAllocation) * 100;
            
            $data[$branch] = round($allocationPercentage, 2);
        }        
        
        return  [
            'data' => array_values($data),
            'labels' => array_keys($items),
        ];
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
            'data' => $data,
            'labels' => $labels,
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
            $allocation = $allocationItem['allocation'] / 1000;
            $data[] = round(($allocation / $totalAllocated) * 100, 2);
        }

        return [
            'data' => $data,
            'labels' => $labels,
            'numActivePosition' => $numActivePosition,
            'numPosition' => $numActivePosition,
            'numTickers' => $numTickers,
            'profit' => $profit,
            'totalDividend' => $totalDividend,
            'totalInvested' => $allocated,
        ];
    }
}
