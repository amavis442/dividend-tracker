<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\Position;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Todo: needs to be renamed to make it clear it is for the position only.
 * So should be called PositionWeightedAverage
 */
class WeightedAverage
{
    protected $transactions = [];
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function addTransaction(Transaction $transaction, int $index)
    {
        $timeStamp = (int) $transaction->getTransactionDate()->format('YmdHis');
        if (isset($this->transactions[$timeStamp])) {
            $timeStamp += $index;
        }
        $this->transactions[$timeStamp] = $transaction;
    }

    public function calc(Position $position): void
    {
        $this->transactions = [];
        $transactions = $position->getTransactions();
        $n = 1;
        foreach ($transactions as $transaction) {
            $this->addTransaction($transaction, $n);
            $n++;
        }

        if (empty($this->transactions) || count($this->transactions) === 0) {
            return;
        }
        ksort($this->transactions);

        $totalProfit = 0;
        $avgPrice = 0.000;
        $costBase = 0.0000;
        $numShares = 0.0;
        $aPrice = 0;

        foreach (array_values($this->transactions) as $transaction) {
            $profit = 0.0;
            $amount = $transaction->getAmount();
            $allocation = $transaction->getAllocation(); // This one should be total - all the costs

            if ($transaction->getSide() === Transaction::BUY) {
                $costBase += $allocation;
                $numShares += $amount;
            }

            if ($transaction->getSide() === Transaction::SELL) {
                $calcAllocation = $amount * $avgPrice;
                $profit = $transaction->getProfit(); //round($allocation - $calcAllocation, 3);
                $numShares -= $amount;
                $costBase -= $calcAllocation; //$allocation;
                $totalProfit += $profit;
            }

            if ($costBase > 0 && $numShares > 0) {
                $avgPrice = $costBase / $numShares;
                $aPrice = round($avgPrice, 3);
                $transaction->setAvgprice($aPrice);
            }
        }

        $position->setAllocation(round($costBase, 3))
            ->setAmount((float) $numShares)
            ->setPrice($aPrice)
            ->setProfit(round($totalProfit, 3));
    }
}
