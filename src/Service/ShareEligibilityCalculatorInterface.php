<?php

namespace App\Service;

use Doctrine\Common\Collections\Collection;
use App\Entity\Calendar;
use App\Entity\Transaction;

interface ShareEligibilityCalculatorInterface
{
    /**
     * Calculate the number of shares eligible for dividend
     * based on transaction history and ex-dividend cutoff.
     */
    public function calculate(Collection $transactions, Calendar $calendar): float;
}
