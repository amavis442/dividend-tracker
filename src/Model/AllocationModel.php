<?php

namespace App\Model;

use App\Repository\BranchRepository;
use App\Repository\PositionRepository;
use App\Service\SummaryService;
use Symfony\Contracts\Translation\TranslatorInterface;

class AllocationModel
{
    public function allocation(
        PositionRepository $positionRepository,
        TranslatorInterface $translator
    ): array {
        $positions = $positionRepository->getAllOpen();

        $data = [];
        $items = [];
        $totalAllocation = 0.0;
        foreach ($positions as $position) {
            $label = $translator->trans($position->getTicker()->getBranch()->getLabel());
            if (!isset($items[$label])) {
                $items[$label] = 0.0;
            }
            $allocation = $position->getAllocation();
            $items[$label] += $allocation;
            $totalAllocation += $allocation;
        }
        krsort($items);

        foreach ($items as $branch => $allocation) {
            $allocationPercentage = ($allocation / $totalAllocation) * 100;

            $data[$branch] = round($allocationPercentage, 2);
        }

        return [
            'data' => array_values($data),
            'labels' => array_keys($items),
        ];
    }

    public function sector(
        PositionRepository $positionRepository,
        BranchRepository $branchRepository,
        SummaryService $summaryService,
        TranslatorInterface $translator
    ): array {
        $summary = $summaryService->getSummary();

        $sectors = $branchRepository->getAllocationPerSector();
        $totalAllocated = $positionRepository->getSumAllocated();

        $allocationData = $positionRepository->getAllocationDataPerSector();
        $labels = [];
        $data = [];
        foreach ($allocationData as $allocationItem) {
            $labels[] = $translator->trans($allocationItem['industry']);
            $allocation = $allocationItem['allocation'] / 100;
            $data[] = round(($allocation / $totalAllocated) * 100, 2);
        }

        return [
            'data' => $data,
            'labels' => $labels,
            'sectors' => $sectors,
            'numActivePosition' => $summary->getNumActivePosition(),
            'numPosition' => $summary->getNumActivePosition(),
            'numTickers' => $summary->getNumTickers(),
            'profit' => $summary->getProfit(),
            'totalDividend' => $summary->getTotalDividend(),
            'totalInvested' => $summary->getAllocated(),
        ];
    }

    public function position(
        PositionRepository $positionRepository,
        SummaryService $summaryService
    ): array {
        $summary = $summaryService->getSummary();

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
            'numActivePosition' => $summary->getNumActivePosition(),
            'numPosition' => $summary->getNumActivePosition(),
            'numTickers' => $summary->getNumTickers(),
            'profit' => $summary->getProfit(),
            'totalDividend' => $summary->getTotalDividend(),
            'totalInvested' => $summary->getAllocated(),
        ];
    }
}
