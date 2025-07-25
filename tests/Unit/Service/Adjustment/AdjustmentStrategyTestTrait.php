<?php

namespace App\Tests\Unit\Service\Adjustment;

use App\Entity\Transaction;
use App\Entity\CorporateAction;
use App\Service\Adjustment\AdjustmentStrategyInterface;

trait AdjustmentStrategyTestTrait
{
    public function createTransaction(float $amount, \DateTimeInterface $date, int $side): Transaction
    {
        $transaction = new Transaction();
        $transaction->setAmount($amount);
        $transaction->setTransactionDate($date);
        $transaction->setSide($side);

        return $transaction;
    }

    public function createCorporateAction(string $type, float $ratio, \DateTimeInterface $eventDate): CorporateAction
    {
        $action = new CorporateAction();
        $action->setType($type);
        $action->setRatio($ratio);
        $action->setEventDate($eventDate);

        return $action;
    }

    public function assertAdjustment(
        AdjustmentStrategyInterface $strategy,
        Transaction $transaction,
        CorporateAction $action,
        float $expected
    ): void {
        $this->assertTrue($strategy->supports($action), 'Strategy should support this action');
        $adjusted = $strategy->adjustAmount($transaction, $action);
        $this->assertEquals($expected, $adjusted, 'Adjusted amount did not match expected value');
    }
}
