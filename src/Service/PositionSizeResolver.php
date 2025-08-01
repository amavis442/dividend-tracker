<?php

namespace App\Service;

use App\Entity\Calendar;
use App\Entity\Transaction;
use Doctrine\Common\Collections\Collection;
use App\Service\TransactionAdjuster;


class PositionSizeResolver
{
    public function __construct(protected TransactionAdjuster $transactionAdjuster){}

    /**
     * Calculates the effective position size on ex-dividend date,
     * adjusted for splits/reverse-splits.
     */
    public function resolve(Collection $transactions, Collection $actions, Calendar $calendar): float
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
