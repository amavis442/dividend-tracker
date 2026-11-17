<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Position;
use App\Service\Position\WeightedAverage;
use Doctrine\ORM\EntityManagerInterface;

class MetricsUpdateService
{
    public function __construct(
        private WeightedAverage $weightedAverage,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function update(Position $position): void
    {
        $this->weightedAverage->calc($position);
        $this->entityManager->persist($position);
        // Note: caller decides when to flush
    }
}
