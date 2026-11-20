<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Unknown sort fields and directions fall back to a default instead of
 * reaching the query.
 */
#[Group('security')]
final class SortParameterTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const FIELD = 'id) DESC, (SELECT 1';
    private const DIRECTION = 'ASC, t.id';

    /**
     * @return iterable<string, array{string}>
     */
    public static function listPages(): iterable
    {
        $q = '?' . http_build_query(['sort' => self::FIELD, 'orderBy' => self::DIRECTION]);
        $swapped = '?' . http_build_query(['sort' => self::DIRECTION, 'orderBy' => self::FIELD]);
        $path = rawurlencode(self::FIELD) . '/' . rawurlencode(self::DIRECTION);

        yield 'tickers' => ['/nl/dashboard/admin/ticker/' . $q];
        yield 'research' => ['/nl/dashboard/research/' . $q];
        yield 'calendar' => ['/nl/dashboard/calendar/' . $q];
        yield 'portfolio' => ['/nl/dashboard/portfolio/?' . http_build_query(['sortBy' => self::FIELD, 'orderBy' => self::DIRECTION])];
        yield 'payments' => ['/nl/dashboard/payment/' . $swapped];
        yield 'transactions' => ['/nl/dashboard/transaction/list/1/' . $path . $swapped];
        yield 'positions' => ['/nl/dashboard/position/list/1/All/' . $path . '/2' . $swapped];
    }

    #[DataProvider('listPages')]
    public function testUnknownSortValuesFallBackToTheDefault(string $url): void
    {
        $client = static::createClient();
        $email = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']])->getEmail();
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        self::assertInstanceOf(User::class, $user);
        $client->loginUser($user);

        $client->request('GET', $url);

        self::assertResponseIsSuccessful($url);
    }
}
