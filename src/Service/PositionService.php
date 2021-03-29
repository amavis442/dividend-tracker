<?php

namespace App\Service;

use App\Entity\Position;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use \DateTime;

class PositionService
{
    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    private function presetMetrics(Position $position): void
    {
        if ($position->getAllocation() && empty($position->getPrice())) {
            $position->setPrice($position->getAllocation() / $position->getAmount());
            $position->setCurrency($position->getAllocationCurrency());
        }
        if ($position->getPrice() && empty($position->getAllocation())) {
            $position->setAllocation($position->getPrice() * $position->getAmount());
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
            ->setAmount($position->getAmount())
            ->setPrice($position->getPrice())
            ->setCurrency($position->getCurrency())
            ->setAllocation($position->getAllocation())
            ->setAllocationCurrency($position->getAllocationCurrency())
            ->setTransactionDate($currentDate);

        $position->addTransaction($transaction);

        $position->setClosed(0);
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
