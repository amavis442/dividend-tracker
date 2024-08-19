<?php

namespace App\Service;

use App\Entity\Position;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;

class PositionService
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

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

    public function create(Position $position): void
    {
        $currentDate = new DateTime();
        $this->presetMetrics($position);

        $transaction = new Transaction();
        $transaction->setSide(Transaction::BUY)
            ->setAmount((float) $position->getAmount())
            ->setPrice($position->getPrice())
            ->setCurrency($position->getCurrency())
            ->setAllocation($position->getAllocation())
            ->setAllocationCurrency($position->getAllocationCurrency())
            ->setTransactionDate($currentDate);

        if ($position->getAmount()) {
            $transaction->setAmount((float) $position->getAmount());
        }

        $position->addTransaction($transaction);

        $position->setClosed(false);
        $this->entityManager->persist($transaction);
        $this->entityManager->persist($position);
        $this->entityManager->flush();
    }

    public function update(Position $position): void
    {
        $this->presetMetrics($position);
        $this->entityManager->persist($position);
        $this->entityManager->flush();
    }
}
