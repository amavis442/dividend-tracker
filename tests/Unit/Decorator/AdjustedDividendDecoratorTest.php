<?php

namespace App\Tests\Unit\Decorator;

use App\Decorator\AdjustedPositionDecorator;
use App\Entity\CorporateAction;
use App\Entity\Position;
use App\Entity\Transaction;
use App\Repository\CorporateActionRepository;
use App\Repository\TransactionRepository;
use App\Service\DividendAdjuster;
use App\Service\TransactionAdjuster;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

/**
 * @test
 */
class AdjustedDividendDecoratorTest extends TestCase
{
	public function testDividendAdjusterWithReverseSplit(): void
	{
		$declared = new \DateTimeImmutable('2024-06-01');
		$action1 = new CorporateAction();
		$action1->setEventDate(new \DateTime('2024-06-15'));
		$action1->setRatio(0.5);
		$action2 = new CorporateAction();
		$action2->setEventDate(new \DateTime('2024-07-01'));
		$action2->setRatio(0.2);

		$adjuster = new DividendAdjuster();

		$adjusted = $adjuster->getAdjustedDividend(
			1.0,
			$declared,
			new ArrayCollection([$action1, $action2])
		);
		$this->assertEquals(10.0, $adjusted);
	}
}
