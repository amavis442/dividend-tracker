<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\CorporateAction;
use Doctrine\Common\Collections\Collection;

class TransactionAdjuster implements TransactionAdjusterInterface
{
    /**
     * Adjusts a transaction amount based on corporate actions.
     *
     * @param Transaction $transaction
     * @param Collection<int, CorporateAction> $actions
     * @return float
     */
    public function getAdjustedAmount(Transaction $transaction, Collection $actions): float
    {
        $amount = $transaction->getAmount();
        $txDate = $transaction->getTransactionDate();

        foreach ($actions as $action) {
            if ($txDate < $action->getEventDate()) {
                $amount *= $action->getRatio(); // Applies reverse split
            }
        }

        return $amount;
    }
}
