<?php

namespace App\Tests\Unit\Service;

use App\Entity\Calendar;
use App\Entity\Transaction;
use App\Service\ShareEligibilityCalculator;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class ShareEligibilityCalculatorTest extends TestCase
{
    public function testCalculateEligibleShares(): void
    {
        // Setup transactions
        $transaction1 = new Transaction();
        $transaction1->setTransactionDate(new \DateTime('2025-06-01'));
        $transaction1->setShares(100);

        $transaction2 = new Transaction();
        $transaction2->setTransactionDate(new \DateTime('2025-07-01')); // After ex-date
        $transaction2->setShares(50);

        $transactions = new ArrayCollection([$transaction1, $transaction2]);

        // Setup calendar
        $calendar = new Calendar();
        $calendar->setExDividendDate(new \DateTime('2025-06-15'));

        // Instantiate calculator
        $calculator = new ShareEligibilityCalculator();

        // Run calculation
        $result = $calculator->calculate($transactions, $calendar);

        // Assert only shares before ex-date are counted
        $this->assertEquals(100.0, $result);
    }
}
