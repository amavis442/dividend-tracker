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

		$user = UserFactory::createOne(['email' => 'test@test.nl']);
        $this->assertSame('test@test.nl', $user->getEmail());

        $this->manager->persist($user->_real());
		$this->manager->flush();


		$userRepository = static::getContainer()->get(UserRepository::class);
		$this->testUser = $userRepository->findOneByEmail('test@test.nl');
		//dump('test user data:', $testUser);

        // simulate $testUser being logged in
        $this->client->loginUser($this->testUser);

		/* using ResetDatabase already
		foreach ($this->corporateActionRepository->findAll() as $object) {
			$this->manager->remove($object);
		}

		$this->manager->flush();
		*/
	}

	public function testIndex(): void
	{
		$this->client->followRedirects();
		$crawler = $this->client->request('GET', $this->path);

		self::assertResponseStatusCodeSame(200);
		self::assertPageTitleContains('CorporateAction index');

		//dump($crawler);

		// Use the $crawler to perform additional assertions e.g.
		// self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
	}

	private function setUpPosition(): \App\Entity\Position
	{
		//$start = microtime(true);
		$currency = CurrencyFactory::createOne(['symbol'=> 'USD']);
		//$end = microtime(true);
		//dump('CurrencyFactory took ' . ($end - $start) . ' seconds');

        //$start = microtime(true);
		$branch = BranchFactory::createOne(['label' => 'finance']);
		//$end = microtime(true);
		//dump('BranchFactory took ' . ($end - $start) . ' seconds');

        //$start = microtime(true);
		$ticker = TickerFactory::createOne([
            'branch' => $branch,
            'fullname' => 'Apple',
            'symbol' => 'AAPL',
        ]);
		//$end = microtime(true);
		//dump('TickerFactory took ' . ($end - $start) . ' seconds');

        //$start = microtime(true);
		//$user = UserFactory::new();
		//$end = microtime(true);
		//dump('CurrencyFactory took ' . ($end - $start) . ' seconds');

		$positionProxy = PositionFactory::createOne([
			'allocation' => 1000.0,
			'amount' => 10.0,
			'closed' => false,
			'currency' => $currency,
			'ignore_for_dividend' => false,
			'price' => 10.0,
			'profit' => 0.0,
			'ticker' => $ticker,
			'user' => $this->testUser,
		]);
		$position = $positionProxy->_real();

		$this->manager->persist($position);
		$this->manager->flush();

		return $position;
	}


	public function testNew(): void
	{
		$position = $this->setUpPosition();

		//$positionRepository = static::getContainer()->get(PositionRepository::class);
		//$positions = $positionRepository->findAll();
		//dd('Current positions:', $positions);

		$crawler = $this->client->request('GET', sprintf('%snew', $this->path));
		self::assertResponseStatusCodeSame(200);

		//dump($crawler);

		$this->client->submitForm('Save', [
			'corporate_action[type]' => 'reverse_split',
			'corporate_action[eventDate][day]' => 25,
			'corporate_action[eventDate][month]' => 7,
			'corporate_action[eventDate][year]' => 2025,
			'corporate_action[ratio]' => '0.5',
			'corporate_action[position]' => $position->getId(),
		]);

		self::assertResponseRedirects('/nl/dashboard/corporate/action');

		self::assertSame(1, $this->corporateActionRepository->count([]));
	}

	public function testShow(): void
	{
		$position = $this->setUpPosition();

		$fixture = new CorporateAction();
		$fixture->setType('My Test Type');
		$fixture->setEventDate(new \DateTime());
		$fixture->setRatio(2);
		$fixture->setCreatedAt(new \DateTimeImmutable());
		$fixture->setPosition($position);

		$this->manager->persist($fixture);
		$this->manager->flush();

		$crawler = $this->client->request(
			'GET',
			sprintf('%s%s', $this->path, $fixture->getId())
		);

		//dump($crawler);
		self::assertResponseStatusCodeSame(200);
		self::assertPageTitleContains('CorporateAction');
		//self::assertContains('My Test Type',$crawler->filter('.table')->filter('td'), 'Missing My Test Type');
		// Use assertions to check that the properties are properly displayed.
	}

	public function testEdit(): void
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
