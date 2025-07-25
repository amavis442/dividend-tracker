<?php

namespace App\Tests\Unit\Service\Adjustment;

use PHPUnit\Framework\TestCase;
use App\Service\Adjustment\ReverseSplitStrategy;

class ReverseSplitStrategyTest extends TestCase
{
    use AdjustmentStrategyTestTrait;

    public function testAdjustmentAcrossTimeline(): void
    {
        $strategy = new ReverseSplitStrategy();
        $eventDate = new \DateTime('2024-07-01');
        $ratio = 0.25;

        $action = $this->createCorporateAction('reverse_split', $ratio, $eventDate);

        $cases = [
            [100.0, '2024-06-10', 25.0],
            [50.0, '2024-06-20', 12.5],
            [25.0, '2024-06-30', 6.25],
            [75.0, '2024-07-01', 75.0], // strategy keeps original on split date
            [200.0, '2024-07-05', 200.0],
            [80.0, '2024-07-15', 80.0],
        ];

        foreach ($cases as [$original, $date, $expected]) {
            $transaction = $this->createTransaction($original, new \DateTime($date), 2);
            $this->assertAdjustment($strategy, $transaction, $action, $expected);
        }
    }
}
