<?php

namespace App\Service\Position;

use App\Entity\Calendar;
use App\Entity\Transaction;
use App\Service\Transaction\TransactionAdjuster;


class PositionSizeResolver
{
    public function __construct(protected TransactionAdjuster $transactionAdjuster){}

    /**
     * Calculates the effective position size on ex-dividend date,
     * adjusted for splits/reverse-splits.
     */
    public function resolve(array $transactions, array $actions, Calendar $calendar): float
    {
        $exDate = $calendar->getExdividendDate();
        $shares = 0.0;

        foreach ($transactions as $transaction) {
            if ($transaction->getTransactionDate() >= $exDate) {
                continue; // Only shares before ex-div are eligible
            }

            //$amount = $transaction->getAmount();
            $side = $transaction->getSide();

            $adjusted = $this->transactionAdjuster->getAdjustedAmount($transaction, $actions);
            $shares += ($side === Transaction::BUY ? $adjusted : -$adjusted);
        }

        return max(0.0, $shares);
    }
}
