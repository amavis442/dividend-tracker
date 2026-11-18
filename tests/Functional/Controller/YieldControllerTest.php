<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Factory\CalendarFactory;
use App\Factory\CurrencyFactory;
use App\Factory\PositionFactory;
use App\Factory\TickerFactory;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use App\Service\ExchangeRate\WisselkoersNlExchangeRateService;
use App\Tests\Support\StubExchangeRates;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[Group('controller')]
final class YieldControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const PATH = '/nl/dashboard/report/yield';

    private KernelBrowser $client;
    private User $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        static::getContainer()->set(WisselkoersNlExchangeRateService::class, new StubExchangeRates());

        $email = UserFactory::createOne()->getEmail();
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        self::assertInstanceOf(User::class, $user);
        $this->user = $user;
        $this->client->loginUser($user);
    }

    public function testTheReportRendersWithoutPositions(): void
    {
        $this->client->request('GET', self::PATH);

        self::assertResponseIsSuccessful();
    }

    public function testThePieReportRendersWithoutPositions(): void
    {
        $this->client->request('GET', '/nl/dashboard/report/pieyield');

        self::assertResponseIsSuccessful();
    }

    public function testTheReportListsAnOpenPosition(): void
    {
        $this->createPosition();

        $this->client->request('GET', self::PATH);

        self::assertResponseIsSuccessful();
        self::assertAnySelectorTextContains('td', 'YLD');
    }

    public function testAnUnknownSortFallsBackToTheSymbol(): void
    {
        $this->createPosition();

        $this->client->request('GET', self::PATH . '?sort=nonsense&sortDirection=nonsense');

        self::assertResponseIsSuccessful();
        self::assertAnySelectorTextContains('td', 'YLD');
    }

    private function createPosition(): void
    {
        $currency = CurrencyFactory::createOne(['symbol' => 'USD']);
        $ticker = TickerFactory::createOne(['symbol' => 'YLD', 'fullname' => 'Yield Income']);
        CalendarFactory::new()
            ->instantiateWith(Instantiator::withConstructor()->alwaysForce())
            ->create(['ticker' => $ticker, 'currency' => $currency, 'createdAt' => new \DateTimeImmutable()]);
        PositionFactory::createOne([
            'ticker' => $ticker,
            'user' => $this->user,
            'currency' => $currency,
            'closed' => false,
            'ignore_for_dividend' => false,
            'allocation' => 100.0,
            'amount' => 10.0,
            'price' => 10.0,
        ]);
    }
}
