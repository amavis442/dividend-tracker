<?php
namespace App\Tests\Unit\Shares;

use PHPUnit\Framework\TestCase;
use App\Entity\Transaction;
use App\Entity\CorporateAction;
use App\Service\Transaction\TransactionAdjuster;

/**
 * When an corporate event happens like a split, we need to adjust the transaction shares amount, but keep
 * the database intact for history.
 */
class TransactionAdjustTest extends TestCase
{
	public function testReverseSplitAdjustsTransactionAmount(): void
	{
		$transaction = new Transaction();
		$transaction->setAmount(100);
		$transaction->setTransactionDate(new \DateTime('2025-06-01'));

		$action = new CorporateAction();
		$action->setEventDate(new \DateTime('2025-07-01'));
		$action->setRatio(0.5); // 2:1 reverse split

		$adjuster = new TransactionAdjuster();
		$adjusted = $adjuster->getAdjustedAmount(
			$transaction,
			[$action]
		);

		$this->assertEquals(50.0, $adjusted);
	}
}
