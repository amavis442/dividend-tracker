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

        $totalAllocation = 0;
        $totalAmount = 0;
        $totalProfit = 0;
        $avgPrice = 0;
        foreach ($this->transactions as $timeStamp => $transaction) {
            $profit = 0;
            $amount = $transaction->getAmount();
            $allocation = $transaction->getAllocation();
            
            if ($transaction->getSide() === Transaction::BUY) {
                $totalAmount += $amount;
                $totalAllocation += $allocation;
            }
            if ($transaction->getSide() === Transaction::SELL) {
                $profit = (int) round($allocation - $amount * $avgPrice, 0);
                $transaction->setProfit($profit);

                $totalAmount -= $amount;
                $totalAllocation = $totalAmount * $avgPrice;
                if ($profit < 0) { //loss
                    $totalAllocation -= $profit;
                }
                $totalProfit += $profit;
            }
        
            if ($totalAmount > 0) {
                $avgPrice = $totalAllocation / $totalAmount;
                $aPrice = (int) round($avgPrice * 100, 0);
                $transaction->setAvgprice($aPrice);
            }    
        }
        
        $position->setAllocation((int) round($totalAllocation))
            ->setAmount($totalAmount)
            ->setPrice($aPrice)
            ->setProfit((int) round($totalProfit));
    }
}
