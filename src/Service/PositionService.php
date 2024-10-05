<?php

namespace App\Service;

use App\Entity\Position;
use Doctrine\ORM\EntityManagerInterface;

class PositionService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    private function presetMetrics(Position $position): void
    {
        if ($position->getAllocation() && empty($position->getPrice()) && $position->getAmount()) {
            $position->setPrice($position->getAllocation() / (float) $position->getAmount());
            $position->setCurrency($position->getAllocationCurrency());
        }
        if ($position->getPrice() && empty($position->getAllocation()) && $position->getAmount()) {
            $position->setAllocation($position->getPrice() * (float) $position->getAmount());
            $position->setAllocationCurrency($position->getCurrency());
        }

        if ($position->getClosed()) {
            $position->setAllocation(0);
        }
    }

    public function update(Position $position): void
    {
        $this->presetMetrics($position);
        $this->entityManager->persist($position);
        $this->entityManager->flush();
    }
}
