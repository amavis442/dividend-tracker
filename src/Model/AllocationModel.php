<?php

namespace App\Model;

use App\Repository\PositionRepository;
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
            $allocationPercentage = $totalAllocation > 0 ? ($allocation / $totalAllocation) * 100 : 0.0;

            $data[$branch] = round($allocationPercentage, 2);
        }

        return [
            'data' => array_values($data),
            'labels' => array_keys($items),
        ];
    }
}
