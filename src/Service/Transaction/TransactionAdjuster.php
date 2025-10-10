<?php

namespace App\Service\Transaction;

use App\Entity\Transaction;
use App\Entity\CorporateAction;

class TransactionAdjuster implements TransactionAdjusterInterface
{
    /**
     * Adjusts a transaction amount based on corporate actions.
     *
     * @param Transaction $transaction
     * @param array<int, CorporateAction> $actions
     * @return float
     */
    public function getAdjustedAmount(Transaction $transaction, array $actions): float
    {
        $amount = $transaction->getAmount();
        $txDate = $transaction->getTransactionDate();

        foreach ($actions as $action) {
            if ($txDate < $action->getEventDate()) {
                $amount *= $action->getRatio(); // Applies reverse split
            }
        }

        $transaction->setAdjustedAmount($amount);

        return $amount;
    }
}
