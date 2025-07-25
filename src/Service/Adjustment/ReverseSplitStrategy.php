<?php

namespace App\Service\Adjustment;

use App\Entity\CorporateAction;
use App\Entity\Transaction;

class ReverseSplitStrategy implements AdjustmentStrategyInterface
{
    public function supports(CorporateAction $action): bool
    {
        return $action->getType() === 'reverse_split';
    }

    public function adjustAmount(Transaction $transaction, CorporateAction $action): float
    {
        if ($transaction->getTransactionDate() < $action->getEventDate()) {
            return $transaction->getAmount() * $action->getRatio();
        }

        return $transaction->getAmount();
    }
}
