<?php
namespace App\Decorator;

use App\Entity\Position;
use App\Entity\Transaction;
use App\Repository\CorporateActionRepository;
use App\Repository\TransactionRepository;
use App\Service\TransactionAdjuster;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class AdjustedPositionDecorator
{
    public function __construct(
        private Position $position,
        private TransactionRepository $transactionRepo,
        private CorporateActionRepository $actionRepo,
        private TransactionAdjuster $transactionAdjuster,
    ) {}

    public function getAdjustedAmount(): float
    {
        $transactions = $this->transactionRepo->findBy(['position' => $this->position->getId()]);
        $actions = $this->actionRepo->findBy(['position' => $this->position->getId(), 'type' => 'reverse_split'], ['eventDate' => 'ASC']);

        $total = 0.0;

        foreach ($transactions as $tx) {
            $adjustedAmount = $this->transactionAdjuster->getAdjustedAmount($tx, new ArrayCollection($actions));

            $side = $tx->getSide();
            $total += ($side === Transaction::BUY ? $adjustedAmount : -$adjustedAmount);
        }

        return round($total, 4);
    }

    public function getAdjustedAveragePrice(Collection $transactions, Collection $actions): float
    {
        $transactions = $this->transactionRepo->findBy(['position' => $this->position->getId()]);
        $actions = $this->actionRepo->findBy(['position' => $this->position->getId(), 'type' => 'reverse_split'], ['eventDate' => 'ASC']);

        $totalShares = 0.0;
        $totalCost = 0.0;

        foreach ($transactions as $tx) {
            $amount = $tx->getAmount();
            $price = $tx->getPrice();
            $txDate = $tx->getTransactionDate();

            foreach ($actions as $action) {
                if ($txDate < $action->getEventDate()) {
                    $amount *= $action->getRatio();
                }
            }

            $side = $tx->getSide();
            if ($side === 1) {
                $totalShares += $amount;
                $totalCost += $amount * $price;
            } elseif ($side === 2) {
                $totalShares -= $amount;
                $totalCost -= $amount * $price;
            }
        }

        return $totalShares > 0 ? round($totalCost / $totalShares, 4) : 0.0;
    }

    public function getAdjustmentNote(): ?string
    {
        $actions = $this->actionRepo->findBy(['position' => $this->position->getId(), 'type' => 'reverse_split'], ['eventDate' => 'ASC']);

        if (empty($actions)) {
            return null;
        }

        $notes = array_map(function ($action) {
            return sprintf(
                "Adjusted due to reverse split on %s (ratio: %s)",
                $action->getEventDate()->format('Y-m-d'),
                $action->getRatio()
            );
        }, $actions);

        return implode('; ', $notes);
    }

    public function getOriginalPosition(): Position
    {
        return $this->position;
    }

    public function getSymbol(): string
    {
        return $this->position->getTicker()->getSymbol();
    }
}
