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
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[Group('controller')]
final class ExchangeRateUnavailableTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testAPageNeedingARateReportsItIsUnavailable(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        static::getContainer()->set(WisselkoersNlExchangeRateService::class, new StubExchangeRates([]));

        $email = UserFactory::createOne()->getEmail();
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        self::assertInstanceOf(User::class, $user);

        $currency = CurrencyFactory::createOne(['symbol' => 'USD']);
        $ticker = TickerFactory::createOne();
        CalendarFactory::new()
            ->instantiateWith(Instantiator::withConstructor()->alwaysForce())
            ->create(['ticker' => $ticker, 'currency' => $currency, 'createdAt' => new \DateTimeImmutable()]);
        PositionFactory::createOne([
            'ticker' => $ticker,
            'user' => $user,
            'currency' => $currency,
            'closed' => false,
            'allocation' => 100.0,
            'amount' => 10.0,
        ]);
        $client->loginUser($user);
        $client->catchExceptions(true);

        $client->request('GET', '/nl/dashboard/report/yield');

        self::assertResponseStatusCodeSame(503);
    }
}
