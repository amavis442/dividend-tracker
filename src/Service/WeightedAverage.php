<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\Position;

class WeightedAverage
{
    protected $transactions = [];
    protected $resultSet = [];

    public function __construct()
    {
        $this->resultSet = [
            'position' => [],
            'transactions' => []
        ];
    }

    public function addTransaction(Transaction $transaction, int $index)
    {
        $timeStamp = (int)$transaction->getTransactionDate()->format('YmdHis');
        if (isset($this->transactions[$timeStamp])) {
            $timeStamp += $index;
        }
        $this->transactions[$timeStamp] = $transaction;
    }

    public function calc(Position $position): void
    {
        $transactions = $position->getTransactions();
        $n = 1;
        foreach ($transactions as $transaction) {
            $this->addTransaction($transaction, $n);
            $n++;
        }

        if (count($this->transactions) === 0) {
            return;
        }
        ksort($this->transactions);

        $totalProfit = 0;
        $avgPrice = 0;
        $costBase = 0.0;
        $numShares = 0.0;

        foreach ($this->transactions as $timeStamp => $transaction) {
            $profit = 0.0;
            $amount = $transaction->getAmount();
            $allocation = $transaction->getAllocation();

            if ($transaction->getSide() === Transaction::BUY) {
                $costBase += $allocation;
                $numShares += $amount;
            }

            if ($transaction->getSide() === Transaction::SELL) {
                $calcAllocation = $amount * $avgPrice;
                $profit = (int) round($allocation - $calcAllocation, 0);
                $transaction->setProfit($profit);

                $numShares -= $amount;
                $costBase -= $calcAllocation;//$allocation;
                $totalProfit += $profit;
            }

            if ($costBase > 0) {
                $avgPrice = $costBase / $numShares;
                $aPrice = (int) round($avgPrice * 100, 0);
                $transaction->setAvgprice($aPrice);
            }
        }

        $position->setAllocation((int) round($costBase))
            ->setAmount($numShares)
            ->setPrice($aPrice)
            ->setProfit((int) round($totalProfit));
    }
}
