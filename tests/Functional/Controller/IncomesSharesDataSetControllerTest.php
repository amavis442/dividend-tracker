<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Contracts\ExchangeRate\ExchangeRateInterface;
use App\Entity\IncomesSharesData;
use App\Entity\IncomesSharesDataSet;
use App\Entity\Position;
use App\Entity\User;
use App\Factory\CalendarFactory;
use App\Factory\PositionFactory;
use App\Factory\TickerFactory;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use App\Service\ExchangeRate\WisselkoersNlExchangeRateService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[Group('controller')]
final class IncomesSharesDataSetControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const BASE = '/nl/dashboard/trading212/incomesshares';
    private const ISIN = 'IE00TEST0001';

    private KernelBrowser $client;
    private Position $position;

    protected function setUp(): void
    {
        $_ENV['INCOMESSHARES'] = self::ISIN;
        $_SERVER['INCOMESSHARES'] = self::ISIN;

        $this->client = static::createClient();
        $this->client->disableReboot();

        $exchangeRates = $this->createConfiguredStub(ExchangeRateInterface::class, [
            'getRates' => ['USD' => 2.0],
        ]);
        static::getContainer()->set(
            WisselkoersNlExchangeRateService::class,
            $exchangeRates
        );

        $email = UserFactory::createOne()->getEmail();
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        self::assertInstanceOf(User::class, $user);

        $ticker = TickerFactory::createOne(['isin' => self::ISIN, 'fullname' => 'Test Income']);
        CalendarFactory::new()
            ->instantiateWith(Instantiator::withConstructor()->alwaysForce())
            ->create(['ticker' => $ticker, 'cashAmount' => 0.5, 'createdAt' => new \DateTimeImmutable()]);
        $this->position = PositionFactory::createOne([
            'ticker' => $ticker,
            'user' => $user,
            'closed' => false,
            'allocation' => 100.0,
            'amount' => 10.0,
        ]);

        $this->client->loginUser($user);
    }

    protected function tearDown(): void
    {
        unset($_ENV['INCOMESSHARES'], $_SERVER['INCOMESSHARES']);

        parent::tearDown();
    }

    public function testCalculatingAnExistingDataSetRendersTheResult(): void
    {
        $this->calculate(sprintf('%s/%d/edit', self::BASE, $this->createDataSet()->getId()));
    }

    public function testCalculatingANewDataSetRendersTheResult(): void
    {
        $this->calculate(self::BASE . '/create');
    }

    private function calculate(string $path): void
    {
        $crawler = $this->client->request('GET', $path);
        self::assertResponseIsSuccessful();

        $this->client->submit($crawler->filter('button[name$="[submit]"]')->form());

        self::assertResponseIsSuccessful();
        self::assertAnySelectorTextContains('turbo-frame#incomesshares-results td', '2,50');
    }

    private function createDataSet(): IncomesSharesDataSet
    {
        $uuid = Uuid::v7();
        $now = new \DateTimeImmutable();

        $dataSet = new IncomesSharesDataSet();
        $dataSet->setUuid($uuid);
        $dataSet->setCreatedAt($now);

        $share = new IncomesSharesData();
        $share->setTicker($this->position->getTicker());
        $share->setPosition($this->position);
        $share->setPrice(11.0);
        $share->setProfitLoss(10.0);
        $share->setAllocation(100.0);
        $share->setAmount(10.0);
        $share->setDistributions(3.0);
        $share->setDataset($uuid);
        $share->setCreatedAt($now);
        $share->setUpdatedAt($now);
        $share->setIncomesSharesDataSet($dataSet);
        $dataSet->getShares()->add($share);

        $manager = static::getContainer()->get(EntityManagerInterface::class);
        $manager->persist($dataSet);
        $manager->persist($share);
        $manager->flush();

        return $dataSet;
    }
}
