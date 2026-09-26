<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Entity\CorporateAction;
use App\Repository\UserRepository;
use App\Repository\PositionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use App\Factory\PositionFactory;
use App\Factory\TickerFactory;
use App\Factory\CurrencyFactory;
use App\Factory\UserFactory;
use App\Factory\BranchFactory;


#[Group('controller')]
final class CorporateActionControllerTest extends WebTestCase
{
	use Factories;
	use ResetDatabase;

	private KernelBrowser $client;
	private EntityManagerInterface $manager;
	private EntityRepository $corporateActionRepository;
	private string $path = '/nl/dashboard/corporate/action/';

	private User $testUser;

	protected function setUp(): void
	{
		$this->client = static::createClient();
		$this->manager = static::getContainer()->get('doctrine')->getManager();
		$this->corporateActionRepository = $this->manager->getRepository(
			CorporateAction::class
		);

		$userFactoryResult = UserFactory::createOne(['email' => '[EMAIL]']);
		$this->manager->flush();  // flush so repository lookup works

		$user = $userFactoryResult->_real();
		$this->assertSame('[EMAIL]', $user->getEmail());

		$userRepository = static::getContainer()->get(UserRepository::class);
		$this->testUser = $userRepository->findOneByEmail('[EMAIL]');

        // simulate $testUser being logged in
        $this->client->loginUser($this->testUser);
	}

	public function testIndex(): void
	{
		$this->client->followRedirects();
		$crawler = $this->client->request('GET', $this->path);

		self::assertResponseStatusCodeSame(200);
		self::assertPageTitleContains('CorporateAction index');
	}

	private function setUpTicker(): \App\Entity\Ticker
	{
		$currency = CurrencyFactory::createOne(['symbol'=> 'USD']);
		$branch = BranchFactory::createOne(['label' => 'finance']);

		$tickerProxy = TickerFactory::createOne([
            'branch' => $branch,
            'fullname' => 'Apple',
            'symbol' => 'AAPL',
        ]);

		$positionProxy = PositionFactory::createOne([
			'allocation' => 1000.0,
			'amount' => 10.0,
			'closed' => false,
			'currency' => $currency,
			'ignore_for_dividend' => false,
			'price' => 10.0,
			'profit' => 0.0,
			'ticker' => $tickerProxy,
			'user' => $this->testUser,
		]);
		$position = $positionProxy->_real();
		$ticker = $tickerProxy->_real();

		$this->manager->persist($ticker);
		$this->manager->persist($position);
		$this->manager->flush();

		return $ticker;
	}


	public function testNew(): void
	{
		$ticker = $this->setUpTicker();

		$crawler = $this->client->request('GET', sprintf('%snew', $this->path));
		self::assertResponseStatusCodeSame(200);

		$this->client->submitForm('Save', [
			'corporate_action[type]' => 'reverse_split',
			'corporate_action[eventDate]' => '2025-07-25',
			'corporate_action[ratio]' => '0.5',
			'corporate_action[ticker]' => $ticker->getId(),
		]);

		self::assertResponseRedirects('/nl/dashboard/corporate/action');

		self::assertSame(1, $this->corporateActionRepository->count([]));
	}

	public function testShow(): void
	{
		$ticker = $this->setUpTicker();

		$fixture = new CorporateAction();
		$fixture->setType('My Test Type');
		$fixture->setEventDate(new \DateTime());
		$fixture->setRatio(2);
		$fixture->setCreatedAt(new \DateTimeImmutable());
		$fixture->setTicker($ticker);

		$this->manager->persist($fixture);
		$this->manager->flush();

		$crawler = $this->client->request(
			'GET',
			sprintf('%s%s', $this->path, $fixture->getId())
		);

		self::assertResponseStatusCodeSame(200);
		self::assertPageTitleContains('CorporateAction');
	}

	public function testEdit(): void
	{
		$this->markTestIncomplete();
		$fixture = new CorporateAction();
		$fixture->setType('Value');
		$fixture->setEventDate('Value');
		$fixture->setRatio('Value');
		$fixture->setCreatedAt('Value');
		$fixture->setTicker(1);

		$this->manager->persist($fixture);
		$this->manager->flush();

		$this->client->request(
			'GET',
			sprintf('%s%s/edit', $this->path, $fixture->getId())
		);

		$this->client->submitForm('Update', [
			'corporate_action[type]' => 'Something New',
			'corporate_action[eventDate]' => 'Something New',
			'corporate_action[ratio]' => 'Something New',
			'corporate_action[createdAt]' => 'Something New',
			'corporate_action[position]' => 'Something New',
		]);

		self::assertResponseRedirects('/corporate/action/');

		$fixture = $this->corporateActionRepository->findAll();

		self::assertSame('Something New', $fixture[0]->getType());
		self::assertSame('Something New', $fixture[0]->getEventDate());
		self::assertSame('Something New', $fixture[0]->getRatio());
		self::assertSame('Something New', $fixture[0]->getCreatedAt());
		self::assertSame('Something New', $fixture[0]->getPosition());
	}

	public function testRemove(): void
	{
		$this->markTestIncomplete();
		$fixture = new CorporateAction();
		$fixture->setType('Value');
		$fixture->setEventDate('Value');
		$fixture->setRatio('Value');
		$fixture->setCreatedAt('Value');
		$fixture->setPosition('Value');

		$this->manager->persist($fixture);
		$this->manager->flush();

		$this->client->request(
			'GET',
			sprintf('%s%s', $this->path, $fixture->getId())
		);
		$this->client->submitForm('Delete');

		self::assertResponseRedirects('/nl/dashboard/corporate/action');
		self::assertSame(0, $this->corporateActionRepository->count([]));
	}

}