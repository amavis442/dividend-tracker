<?php

namespace App\Tests\Features\Position;

use App\Decorator\AdjustedPositionDecorator;
use App\Entity\CorporateAction;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Entity\Transaction;
use App\Repository\CorporateActionRepository;
use App\Repository\TransactionRepository;
use App\Service\Transaction\TransactionAdjuster;

use PHPUnit\Framework\TestCase;

/**
 * @test
 */
class AdjustedPositionDecoratorTest extends TestCase
{
	public function testAdjustedAveragePriceWithReverseSplit(): void
	{
		$position = new Position();
		$reflection = new \ReflectionClass($position);
		$property = $reflection->getProperty('id');
		$property->setAccessible(true);
		$property->setValue($position, 1);

		$ticker = new Ticker();
		$reflection = new \ReflectionClass($ticker);
		$property = $reflection->getProperty('id');
		$property->setAccessible(true);
		$property->setValue($ticker, 1);

		$ticker->addPosition($position);

		$transaction = new Transaction();
		$transaction->setPosition($position);
		$transaction->setAmount(100.0);
		$transaction->setPrice(10.0);
		$transaction->setSide(Transaction::BUY);
		$transaction->setTransactionDate(new \DateTime('2024-06-01'));

		$tx2 = new Transaction();
		$tx2->setPosition($position);
		$tx2->setAmount(40.0);
		$tx2->setPrice(11.0);
		$tx2->setSide(Transaction::SELL);
		$tx2->setTransactionDate(new \DateTime('2024-06-10'));

		$tx3 = new Transaction();
		$tx3->setPosition($position);
		$tx3->setAmount(50.0);
		$tx3->setPrice(12.0);
		$tx3->setSide(Transaction::BUY);
		$tx3->setTransactionDate(new \DateTime('2024-07-01')); // after split

		$transactions = [$transaction, $tx2, $tx3];

		$action = new CorporateAction();
		$action->setTicker($ticker);
		$action->setType('reverse_split');
		$action->setRatio(0.2); // 5:1 reverse split
		$action->setEventDate(new \DateTime('2024-06-15'));

		$corporateActions = [$action];

		$adjusterMock = $this->createMock(TransactionAdjuster::class);
		$adjusterMock
			->method('getAdjustedAmount')
			->willReturnCallback(function (Transaction $tx, $actions) {
				$date = $tx->getTransactionDate();
				foreach ($actions as $action) {
					if ($date < $action->getEventDate()) {
						return $tx->getAmount() * $action->getRatio();
					}
				}
				return $tx->getAmount();
			});

		$decorator = new AdjustedPositionDecorator(
			position: $position,
			transactions: $transactions,
			actions: $corporateActions,
			transactionAdjuster: $adjusterMock
		);

		$adjustedPrice = $decorator->getAdjustedAveragePrice();
		$adjustedAmount = $decorator->getAdjustedAmount();

		$this->assertEquals(round(1160 / 62, 4), $adjustedPrice);
		$this->assertEquals(62.0, $adjustedAmount);
	}
}
