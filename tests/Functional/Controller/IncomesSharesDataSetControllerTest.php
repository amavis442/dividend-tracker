<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Contracts\ExchangeRate\ExchangeRateInterface;
use App\Entity\Currency;
use App\Entity\IncomesSharesData;
use App\Entity\IncomesSharesDataSet;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Entity\User;
use App\Factory\CalendarFactory;
use App\Factory\CurrencyFactory;
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
    private const CLOSED_ISIN = 'IE00TEST0002';
    private const RESULT_CELL = 'turbo-frame#incomesshares-results td';

    private KernelBrowser $client;
    private User $user;
    private Currency $currency;
    private Position $position;
    private ExchangeRateInterface $exchangeRates;

    protected function setUp(): void
    {
        $_ENV['INCOMESSHARES'] = self::ISIN . ',' . self::CLOSED_ISIN;
        $_SERVER['INCOMESSHARES'] = $_ENV['INCOMESSHARES'];

        $this->client = static::createClient();
        $this->client->disableReboot();

        $this->exchangeRates = new class implements ExchangeRateInterface {
            /** @var array<string, float> */
            public array $rates = ['USD' => 2.0];

            public function getRates(): array
            {
                return $this->rates;
            }
        };
        static::getContainer()->set(WisselkoersNlExchangeRateService::class, $this->exchangeRates);

        $email = UserFactory::createOne()->getEmail();
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        self::assertInstanceOf(User::class, $user);
        $this->user = $user;
        $this->currency = CurrencyFactory::createOne(['symbol' => 'USD']);

        $this->position = $this->positionFor('Test Income', self::ISIN, closed: false);

        $this->client->loginUser($user);
    }

    protected function tearDown(): void
    {
        unset($_ENV['INCOMESSHARES'], $_SERVER['INCOMESSHARES']);

        parent::tearDown();
    }

    public function testCalculatingAnExistingDataSetRendersTheNetResult(): void
    {
        $this->calculate($this->editPath($this->createDataSet($this->position)));

        self::assertAnySelectorTextContains(self::RESULT_CELL, 'Test Income');
        self::assertAnySelectorTextContains(self::RESULT_CELL, '3,40');
    }

    public function testCalculatingANewDataSetRendersTheNetResult(): void
    {
        $this->calculate(self::BASE . '/create');

        self::assertAnySelectorTextContains(self::RESULT_CELL, '3,40');
    }

    public function testABlankPriceCountsAsZero(): void
    {
        $crawler = $this->client->request('GET', self::BASE . '/create');
        $form = $crawler->filter('button[name$="[submit]"]')->form();
        $field = $crawler->filter('input[name$="[price]"]')->attr('name');
        $form[(string) $field] = '';

        $this->client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertAnySelectorTextContains(self::RESULT_CELL, 'Test Income');
    }

    public function testANewDataSetSkipsTickersWithoutAnOpenPosition(): void
    {
        $this->positionFor('Sold Income', self::CLOSED_ISIN, closed: true);

        $this->calculate(self::BASE . '/create');

        self::assertAnySelectorTextNotContains(self::RESULT_CELL, 'Sold Income');
    }

    public function testCalculatingKeepsATickerWhosePositionIsClosed(): void
    {
        $this->positionFor('Aaa First Open', 'IE00TEST0003', closed: false);
        $closed = $this->positionFor('Sold Income', self::CLOSED_ISIN, closed: true);

        $this->calculate($this->editPath($this->createDataSet($closed)));

        self::assertAnySelectorTextContains(self::RESULT_CELL, 'Sold Income');
        self::assertAnySelectorTextNotContains(self::RESULT_CELL, 'Aaa First Open');
        self::assertAnySelectorTextContains(self::RESULT_CELL, '3,40');
    }

    public function testShowUsesTheStoredShareAmount(): void
    {
        $closed = $this->positionFor('Sold Income', self::CLOSED_ISIN, closed: true, amount: 0.0);

        $this->client->request('GET', sprintf('%s/show/%d', self::BASE, $this->createDataSet($closed)->getId()));

        self::assertResponseIsSuccessful();
        self::assertAnySelectorTextContains('td', '3,40');
    }

    public function testAMissingExchangeRateIsShownAsUnknown(): void
    {
        $this->exchangeRates->rates = [];

        $this->calculate($this->editPath($this->createDataSet($this->position)));

        self::assertAnySelectorTextContains(self::RESULT_CELL, '—');
        self::assertAnySelectorTextNotContains(self::RESULT_CELL, '3,40');
    }

    private function calculate(string $path): void
    {
        $crawler = $this->client->request('GET', $path);
        self::assertResponseIsSuccessful();

        $this->client->submit($crawler->filter('button[name$="[submit]"]')->form());

        self::assertResponseIsSuccessful();
    }

    private function editPath(IncomesSharesDataSet $dataSet): string
    {
        return sprintf('%s/%d/edit', self::BASE, $dataSet->getId());
    }

    private function positionFor(string $fullname, string $isin, bool $closed, float $amount = 10.0): Position
    {
        $ticker = TickerFactory::createOne(['isin' => $isin, 'fullname' => $fullname]);
        $this->calendarFor($ticker);

        return PositionFactory::createOne([
            'ticker' => $ticker,
            'user' => $this->user,
            'currency' => $this->currency,
            'closed' => $closed,
            'allocation' => 100.0,
            'amount' => $amount,
        ]);
    }

    private function calendarFor(Ticker $ticker): void
    {
        CalendarFactory::new()
            ->instantiateWith(Instantiator::withConstructor()->alwaysForce())
            ->create([
                'ticker' => $ticker,
                'currency' => $this->currency,
                'cashAmount' => 0.8,
                'createdAt' => new \DateTimeImmutable(),
            ]);
    }

    private function createDataSet(Position $position): IncomesSharesDataSet
    {
        $uuid = Uuid::v7();
        $now = new \DateTimeImmutable();

        $dataSet = new IncomesSharesDataSet();
        $dataSet->setUuid($uuid);
        $dataSet->setCreatedAt($now);

        $share = new IncomesSharesData();
        $share->setTicker($position->getTicker());
        $share->setPosition($position);
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
