<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Factory\BranchFactory;
use App\Factory\CurrencyFactory;
use App\Factory\PositionFactory;
use App\Factory\TickerFactory;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * The portfolio overview can be sorted by industry (#86).
 */
#[Group('controller')]
final class PortfolioSortTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    // Symbols sort the other way round than their industries.
    private const FINANCE_SYMBOL = 'ZZFIN';
    private const UTILITIES_SYMBOL = 'AAUTL';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $userId = UserFactory::createOne(['email' => 'sort@example.test'])->getId();
        $user = static::getContainer()->get(UserRepository::class)->find($userId);
        self::assertInstanceOf(User::class, $user);

        $currency = CurrencyFactory::createOne(['symbol' => 'USD']);
        foreach ([self::FINANCE_SYMBOL => 'Finance', self::UTILITIES_SYMBOL => 'Utilities'] as $symbol => $industry) {
            PositionFactory::createOne([
                'ticker' => TickerFactory::createOne([
                    'symbol' => $symbol,
                    'fullname' => $symbol . ' Inc',
                    'branch' => BranchFactory::createOne(['label' => $industry]),
                ]),
                'currency' => $currency,
                'user' => $user,
                'closed' => false,
                'ignore_for_dividend' => false,
            ]);
        }

        $this->client->loginUser($user);
        static::getContainer()->get('doctrine')->getManager()->clear();
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function directions(): iterable
    {
        yield 'ascending' => ['asc', self::FINANCE_SYMBOL, self::UTILITIES_SYMBOL];
        yield 'descending' => ['desc', self::UTILITIES_SYMBOL, self::FINANCE_SYMBOL];
    }

    #[DataProvider('directions')]
    public function testThePortfolioCanBeSortedByIndustry(string $direction, string $first, string $second): void
    {
        $this->client->request('GET', '/nl/dashboard/portfolio/?sortBy=industry&orderBy=' . $direction);

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        $firstAt = strpos($content, $first);
        $secondAt = strpos($content, $second);
        self::assertNotFalse($firstAt, $first . ' is listed');
        self::assertNotFalse($secondAt, $second . ' is listed');
        self::assertLessThan($secondAt, $firstAt, $first . ' comes before ' . $second);
    }
}
