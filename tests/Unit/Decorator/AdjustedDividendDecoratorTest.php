<?php

namespace App\Tests\Unit\Decorator;

use App\Entity\CorporateAction;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Entity\Calendar;
use App\Entity\Transaction;
use App\Repository\CorporateActionRepository;
use App\Repository\TransactionRepository;
use App\Service\Dividend\DividendAdjuster;
use App\Service\Transaction\TransactionAdjuster;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use App\Decorator\AdjustedDividendDecorator;

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



	public function testAdjustedDividendWithReverseSplit(): void
	{

		$ticker = new Ticker();
		$reflection = new \ReflectionClass($ticker);
		$property = $reflection->getProperty('id');
		$property->setAccessible(true);
		$property->setValue($ticker, 1);
        $ticker->setSymbol('AAPL');
        $ticker->setFullName('Apple computers');


		$calendar = new Calendar();
		$calendar->setTicker($ticker);
		$calendar->setExDividendDate(new \DateTime('2025-12-01'));
		$calendar->setPaymentDate(new \DateTime('2025-12-31'));
		$calendar->setCashAmount(0.08);
		$reflection = new \ReflectionClass($calendar);
		$property = $reflection->getProperty('createdAt');
		$property->setAccessible(true);
		$property->setValue($calendar, new \DateTimeImmutable('2025-08-20'));

		$property = $reflection->getProperty('id');
		$property->setAccessible(true);
		$property->setValue($calendar, 1);


		$action = new CorporateAction();
		$action->setType('reverse_split');
		$action->setRatio(0.2); // 5:1 reverse split
		$action->setEventDate(new \DateTime('2025-09-15'));

		$ticker->addCorporateAction($action);

		$corporateActions = [$action];
		$dividends = [$calendar];

		$adjuster = new DividendAdjuster();

		$decorator = new AdjustedDividendDecorator(
			dividends: $dividends,
			actions: $corporateActions,
			dividendAdjuster: $adjuster
		);

		$adjustedDividend = $decorator->getAdjustedDividend();

		$this->assertEquals(0.08, $adjustedDividend[$ticker->getId()]['original']);
		$this->assertEquals(0.08 / 0.2, $adjustedDividend[$ticker->getId()]['adjusted']);
	}
}
