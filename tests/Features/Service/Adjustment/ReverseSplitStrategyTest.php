<?php

namespace App\Tests\Features\Service\Adjustment;

use PHPUnit\Framework\TestCase;
use App\Service\Adjustment\ReverseSplitStrategy;
use App\Service\Adjustment\ReverseSplitDividendStrategy;
use App\Service\Adjustment\DividendAdjustmentStrategyResolver;
use App\Entity\CorporateAction;

/**
 * Do we need this test? It is to test different strategies to adjust share mount and dividend amount.
 * Not used.
 */
class ReverseSplitStrategyTest extends TestCase
{
	use AdjustmentStrategyTestTrait;

	public function testAdjustmentAcrossTimeline(): void
	{
		$strategy = new ReverseSplitStrategy();
		$eventDate = new \DateTime('2024-07-01');
		$ratio = 0.25;

		$action = $this->createCorporateAction(
			'reverse_split',
			$ratio,
			$eventDate
		);

		$cases = [
			[100.0, '2024-06-10', 25.0],
			[50.0, '2024-06-20', 12.5],
			[25.0, '2024-06-30', 6.25],
			[75.0, '2024-07-01', 75.0], // strategy keeps original on split date
			[200.0, '2024-07-05', 200.0],
			[80.0, '2024-07-15', 80.0],
		];

		foreach ($cases as [$original, $date, $expected]) {
			$transaction = $this->createTransaction(
				$original,
				new \DateTime($date),
				2
			);
			$this->assertAdjustment(
				$strategy,
				$transaction,
				$action,
				$expected
			);
		}
	}

	public function testDividendAdjustmentWithReverseSplit(): void
	{
		$resolver = new DividendAdjustmentStrategyResolver([
			new ReverseSplitDividendStrategy(),
		]);
		$action = new CorporateAction();
		$action->setType('reverse_split');
		$action->setRatio(0.2);
		$action->setEventDate(new \DateTime('2024-06-15'));

		$adjusted = $resolver->resolve(
			1.0,
			new \DateTime('2024-06-01'),
			$action
		);
		$this->assertEquals(5.0, $adjusted);
	}
}
