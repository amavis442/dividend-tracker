<?php

namespace App\Service\Transaction;

use App\Entity\Calendar;

interface ShareEligibilityCalculatorInterface
{
    /**
     * Calculate the number of shares eligible for dividend
     * based on transaction history and ex-dividend cutoff.
     */
    public function calculate(array $transactions, Calendar $calendar, bool $adjusted = true): float;
    public function filterEligibleTransactions(array $transactions, Calendar $calendar): array;
}
