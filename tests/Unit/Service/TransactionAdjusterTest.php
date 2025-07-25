<?php
namespace App\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use App\Entity\Transaction;
use App\Entity\CorporateAction;
use Doctrine\Common\Collections\ArrayCollection;
use App\Service\TransactionAdjuster;

class TransactionAdjusterTest extends TestCase
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
			new ArrayCollection([$action])
		);

		$this->assertEquals(50.0, $adjusted);
	}
}
