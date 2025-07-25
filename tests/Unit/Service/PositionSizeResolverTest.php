<?php

namespace App\Tests\Unit\Service;

use App\Entity\Calendar;
use App\Entity\CorporateAction;
use App\Entity\Transaction;
use App\Service\PositionSizeResolver;
use App\Service\TransactionAdjuster;
use App\Tests\Unit\Service\Adjustment\AdjustmentStrategyTestTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class PositionSizeResolverTest extends TestCase
{
    use AdjustmentStrategyTestTrait;

    public function testResolvePositionSize(): void
    {
        $exDate = new \DateTime('2024-07-01');

        $calendar = new Calendar();
        $calendar->setExdividendDate($exDate);

        $transactions = new ArrayCollection([
            $this->createTransaction(100.0, new \DateTime('2024-06-10'), Transaction::BUY),
            $this->createTransaction(50.0, new \DateTime('2024-06-20'), Transaction::SELL),
            $this->createTransaction(25.0, new \DateTime('2024-07-01'), Transaction::BUY), // ignored
            $this->createTransaction(80.0, new \DateTime('2024-07-02'), Transaction::BUY), // ignored
        ]);

        $actions = new ArrayCollection([
            $this->createCorporateAction('reverse_split', 0.5, new \DateTime('2024-06-25')),
        ]);

        $adjusterMock = $this->createMock(TransactionAdjuster::class);
        $adjusterMock->method('getAdjustedAmount')->willReturnCallback(
            fn(Transaction $t, $a) => $t->getAmount() * 0.5
        );

        $resolver = new PositionSizeResolver($adjusterMock);
        $result = $resolver->resolve($transactions, $actions, $calendar);

        // BUY 100 * 0.5 = +50
        // SELL 50 * 0.5 = -25
        // = 25 shares
        $this->assertEquals(25.0, $result);
    }
}
