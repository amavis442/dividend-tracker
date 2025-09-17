<?php

namespace App\Service\Adjustment;

use App\Entity\Transaction;
use App\Entity\CorporateAction;

interface AdjustmentStrategyInterface
{
    /**
     * When to apply the strategy
     */
    public function supports(CorporateAction $action): bool;

    public function adjustAmount(Transaction $transaction, CorporateAction $action): float;
}
