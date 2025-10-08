<?php
namespace App\Tests\Unit\Service;

use App\Entity\CorporateAction;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Entity\Transaction;
use App\Repository\CorporateActionRepository;

use App\Repository\TransactionRepository;
use App\Service\Position\WeightedAverage;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class WeightedAverageTest extends TestCase
{
	private $transactionRepoMock;
	private $actionRepoMock;
	private EntityManagerInterface $entityManagerMock;

	protected function setUp(): void
	{
		$this->transactionRepoMock = $this->createMock(
			TransactionRepository::class
		);
		$this->actionRepoMock = $this->createMock(
			CorporateActionRepository::class
		);
		// @disregard
		$this->entityManagerMock = $this->createMock(
			EntityManagerInterface::class
		);

		// @disregard
		$this->service = new WeightedAverage(
			$this->entityManagerMock,
			$this->transactionRepoMock,
			$this->actionRepoMock
		);
	}

	public function testCalcWithReverseSplit()
	{
		$position = new Position();
        $reflection = new \ReflectionClass($position);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($position, 42);

		$ticker = new Ticker();
        $reflection = new \ReflectionClass($ticker);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($ticker, 1);
        $ticker->setSymbol('AAPL');
        $ticker->setFullName('Apple computers');

		$position->setTicker($ticker);

		// Mock transactions
		$tx1 = new Transaction();
		$tx1->setAmount(100);
		$tx1->setPrice(10);
		$tx1->setSide(1); // Buy
		$tx1->setTransactionDate(new DateTime('2022-01-01'));

		$tx2 = new Transaction();
		$tx2->setAmount(50);
		$tx2->setPrice(12);
		$tx2->setSide(1); // Buy
		$tx2->setTransactionDate(new DateTime('2023-01-01'));

		$tx3 = new Transaction();
		$tx3->setAmount(40);
		$tx3->setPrice(11);
		$tx3->setSide(2); // Sell
		$tx3->setTransactionDate(new DateTime('2024-01-01'));

		// Mock corporate action
		$split = new CorporateAction();
        $split->setTicker($ticker);
		$split->setType('reverse_split');
		$split->setRatio(0.1); // 10-for-1 reverse split
		$split->setEventDate(new DateTime('2022-06-01'));

		$this->transactionRepoMock
			->expects($this->once())
			->method('findBy')
			->with(['position' => 42])
			->willReturn([$tx1, $tx2, $tx3]);

		$this->actionRepoMock
			->expects($this->once())
			->method('findBy')
			->with(
				['ticker' => 1, 'type' => 'reverse_split'],
				['eventDate' => 'ASC']
			)
			->willReturn([$split]);

		// Create service and inject dependencies

		$this->service->calc($position);

		// Assertions (use your own getters to fetch calculated values)
		$this->assertEquals(20.0, $position->getAdjustedAmount()); // 100*0.1 + 50 - 40 = 10 + 50 - 40 = 20
		$this->assertEquals(13.0, $position->getAdjustedAveragePrice()); // (10*10 + 50*12 - 40*11) / 20 = 13
	}
}
