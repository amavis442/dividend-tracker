<?php

namespace App\Service\Transaction;

use App\Entity\Transaction;
use Doctrine\Common\Collections\Collection;

interface TransactionAdjusterInterface
{
	public function getAdjustedAmount(
		Transaction $transaction,
		Collection $actions
	): float;
}
