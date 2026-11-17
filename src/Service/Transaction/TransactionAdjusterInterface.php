<?php

declare(strict_types=1);

namespace App\Service\Transaction;

use App\Entity\Transaction;

interface TransactionAdjusterInterface
{
    public function getAdjustedAmount(
        Transaction $transaction,
        array $actions
    ): float;
}
