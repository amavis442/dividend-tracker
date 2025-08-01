<?php

namespace App\Service;

use Doctrine\Common\Collections\Collection;
use App\Entity\Transaction;
use App\Entity\Calendar;

class ShareEligibilityCalculator implements ShareEligibilityCalculatorInterface
{
    public function calculate(Collection $transactions, Calendar $calendar): float
    {
        $shares = 0.0;
        $exDate = $calendar->getExdividendDate();

        foreach ($transactions as $transaction) {
            if ($transaction->getTransactionDate() >= $exDate) {
                continue;
            }

            $amount = $transaction->getAmount();

            $shares += match ($transaction->getSide()) {
                Transaction::BUY => $amount,
                Transaction::SELL => -$amount,
                default => 0.0,
            };
        }

        return $shares;
    }
}
