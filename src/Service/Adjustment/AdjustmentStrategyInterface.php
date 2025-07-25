<?php

namespace App\Service\Adjustment;

use App\Entity\Transaction;
use App\Entity\CorporateAction;

interface AdjustmentStrategyInterface
{
    public function supports(CorporateAction $action): bool;

    public function adjustAmount(Transaction $transaction, CorporateAction $action): float;
}
