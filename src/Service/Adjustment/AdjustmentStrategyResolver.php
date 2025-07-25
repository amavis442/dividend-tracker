<?php

namespace App\Service\Adjustment;

use App\Entity\CorporateAction;
use App\Entity\Transaction;

class AdjustmentStrategyResolver
{
    /**
     * @param AdjustmentStrategyInterface[] $strategies
     */
    public function __construct(private iterable $strategies) {}

    public function resolve(Transaction $transaction, CorporateAction $action): float
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($action)) {
                return $strategy->adjustAmount($transaction, $action);
            }
        }

        return $transaction->getAmount(); // fallback
    }
}
