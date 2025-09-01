<?php

namespace App\Tests\Functional\Service;

use App\Entity\Payment;
use App\Entity\Transaction;
use App\Entity\Position;
use App\Service\Importer\ImportCsvService;
use App\Factory\UserFactory;
use App\Factory\CurrencyFactory;
use App\Factory\TaxFactory;
use App\Factory\BranchFactory;
use App\Factory\TickerFactory;
use App\Factory\PositionFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\SecurityBundle\Security;
use PHPUnit\Framework\Attributes\Group;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Group('import')]
class ImportCsvServiceTest extends KernelTestCase
{
	use Factories;
	use ResetDatabase;

	private ImportCsvService $importCsvService;

	protected function setUp(): void
	{
		self::bootKernel();
		$container = static::getContainer();

		/*$entityManager = $container->get('doctrine.orm.entity_manager');
		$connection = $entityManager->getConnection();

		if (!$connection->isConnected()) {
    		$connection->connect(); // Force a reconnect
		}*/

		$this->importCsvService = $container->get(ImportCsvService::class);
	}

	private function setUpFactories()
	{
		$container = static::getContainer();

		$userFactory = UserFactory::createOne();
		$user = $userFactory->_real();
		$currency = CurrencyFactory::createOne(['symbol' => 'EUR']);
		$tax = TaxFactory::createOne(['taxRate' => 15]);
		$branch = BranchFactory::createOne([
			'label' => 'Unassigned',
			'description' => 'Unassigned',
		]);
		$ticker = TickerFactory::createOne(['isin' => 'US1234567890']);
		PositionFactory::createOne([
			'ticker' => $ticker,
			'user' => $user,
			'amount' => 10,
		]);

		$tokenStorage = $container->get(TokenStorageInterface::class);
		$tokenStorage->setToken(
			new UsernamePasswordToken($user, 'main', $user->getRoles())
		);
	}

	protected function tearDown(): void
	{
		parent::tearDown();

		self::bootKernel();
		$entityManager = self::getContainer()->get('doctrine')->getManager();
		$entityManager->clear(); // Optional: $entityManager->close();
	}

	public function testImportsDividendWithAuthenticatedUser(): void
	{
		$this->setUpFactories();

		// Prepare a simple CSV content with one dividend row
		$csvContent = <<<CSV
Action,Time,ISIN,No. of Shares,Price / Share,Currency (price / share),Exchange Rate,Total,Currency (total),Withholding Tax,Currency (withholding tax),ID
Dividend,2024-06-01 10:00:00,US1234567890,10,0.0,USD,1.0,5.00,USD,0.75,USD,ABC123
CSV;

		$path = sys_get_temp_dir() . '/test_dividends.csv';
		file_put_contents($path, $csvContent);
		$file = new UploadedFile(
			$path,
			'test_dividends.csv',
			'text/csv',
			null,
			true
		);

		// Execute import
		$result = $this->importCsvService->importFile($file);

		// Assertions on import result
		$this->assertSame('ok', $result['status']);
		$this->assertEquals(1, $result['dividendsImported']);

		// Fetch the persisted payment
		$paymentRepo = static::getContainer()
			->get('doctrine')
			->getRepository(Payment::class);

		//$all = $paymentRepo->findAll();

		//dd($all, md5('US12345678902024-06-01 10:00:005'));
		$payment = $paymentRepo->findOneBy([
			'mdHash' => md5('US12345678902024-06-01 10:00:005.00'),
		]);

		$this->assertNotNull($payment);
		$this->assertEquals(5.0, $payment->getDividend());
		$this->assertEquals('USD', $payment->getDividendPaidCurrency());

		// Clean up temporary file
		unlink($path);
	}

	public function testImportsBuyAndSellTransactions(): void
	{
		$this->setUpFactories();

		$csvContent = <<<CSV
Action,Time,ISIN,No. of Shares,Price / Share,Currency (price / share),Exchange Rate,Total,Currency (total),Withholding Tax,Currency (withholding tax),ID
Buy,2024-06-02 12:00:00,US1234567890,5,100.0,USD,1.0,500.00,USD,0.0,USD,TXN1
Sell,2024-06-03 14:00:00,US1234567890,5,120.0,USD,1.0,600.00,USD,0.0,USD,TXN2
CSV;

		$path = sys_get_temp_dir() . '/test_trades.csv';
		file_put_contents($path, $csvContent);
		$file = new UploadedFile(
			$path,
			'test_trades.csv',
			'text/csv',
			null,
			true
		);

		$result = $this->importCsvService->importFile($file);

		$this->assertSame('ok', $result['status']);
		$this->assertEquals(2, $result['transactionsAdded']);
		$this->assertEquals(2, $result['totalTransaction']);

		$transactionRepo = static::getContainer()
			->get('doctrine')
			->getRepository(Transaction::class);

		$buy = $transactionRepo->findOneBy(['jobid' => 'TXN1']);
		$sell = $transactionRepo->findOneBy(['jobid' => 'TXN2']);

		$this->assertNotNull($buy);
		$this->assertNotNull($sell);

		$this->assertEquals(5, $buy->getAmount());
		$this->assertEquals(500.0, $buy->getTotal());

		$this->assertEquals(5, $sell->getAmount());
		$this->assertEquals(600.0, $sell->getTotal());

		unlink($path);
	}

	public function testImportsBuyAndSellTransactionsPositionClosed(): void
	{
		$this->setUpFactories();

		$csvContent = <<<CSV
Action,Time,ISIN,No. of Shares,Price / Share,Currency (price / share),Exchange Rate,Total,Currency (total),Withholding Tax,Currency (withholding tax),ID
Buy,2024-06-02 12:00:00,US1234567890,5,100.0,USD,1.0,500.00,USD,0.0,USD,TXN1
Sell,2024-06-03 14:00:00,US1234567890,5,120.0,USD,1.0,600.00,USD,0.0,USD,TXN2
CSV;

		$path = sys_get_temp_dir() . '/test_trades.csv';
		file_put_contents($path, $csvContent);
		$file = new UploadedFile(
			$path,
			'test_trades.csv',
			'text/csv',
			null,
			true
		);

		$result = $this->importCsvService->importFile($file);

		$this->assertSame('ok', $result['status']);
		$this->assertEquals(2, $result['transactionsAdded']);
		$this->assertEquals(2, $result['totalTransaction']);

		$transactionRepo = static::getContainer()
			->get('doctrine')
			->getRepository(Transaction::class);

		$buy = $transactionRepo->findOneBy(['jobid' => 'TXN1']);

		/**
		 * @var App\Entity\Position
		 */
		$position = $buy->getPosition();

		$this->assertEquals(0.0, (float)$position->getAmount());
		$this->assertTrue($position->getClosed());

		unlink($path);
	}

		public function testImportsBuyAndSellTransactionsPositionStillOpen(): void
	{
		$this->setUpFactories();

		$csvContent = <<<CSV
Action,Time,ISIN,No. of Shares,Price / Share,Currency (price / share),Exchange Rate,Total,Currency (total),Withholding Tax,Currency (withholding tax),ID
Buy,2024-06-02 12:00:00,US1234567890,6,100.0,USD,1.0,500.00,USD,0.0,USD,TXN1
Sell,2024-06-03 14:00:00,US1234567890,5,120.0,USD,1.0,600.00,USD,0.0,USD,TXN2
CSV;

		$path = sys_get_temp_dir() . '/test_trades.csv';
		file_put_contents($path, $csvContent);
		$file = new UploadedFile(
			$path,
			'test_trades.csv',
			'text/csv',
			null,
			true
		);

		$result = $this->importCsvService->importFile($file);

		$this->assertSame('ok', $result['status']);
		$this->assertEquals(2, $result['transactionsAdded']);
		$this->assertEquals(2, $result['totalTransaction']);

		$transactionRepo = static::getContainer()
			->get('doctrine')
			->getRepository(Transaction::class);

		$buy = $transactionRepo->findOneBy(['jobid' => 'TXN1']);

		/**
		 * @var App\Entity\Position
		 */
		$position = $buy->getPosition();

		$this->assertEquals(1.0, (float)$position->getAmount());
		$this->assertFalse($position->getClosed());

		unlink($path);
	}
}
