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
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * The sector and position allocation reports (#82).
 */
#[Group('controller')]
final class AllocationSectorReportTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const PATH = '/nl/dashboard/report/allocation/sector';
    private const POSITION_PATH = '/nl/dashboard/report/allocation/position';

    private KernelBrowser $client;
    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->user = $this->user(UserFactory::createOne(['email' => 'sector@example.test'])->getId());
        $this->otherUser = $this->user(UserFactory::createOne(['email' => 'other@example.test'])->getId());
        // Redis is not available in tests, so nothing would be cached. Use a cache that
        // survives the reset between requests, so the test sees what a second user gets.
        $cache = new FilesystemAdapter('allocation_report_test', 0, sys_get_temp_dir());
        $cache->clear();
        $this->client->disableReboot();
        static::getContainer()->set('cache.app', new TraceableAdapter($cache));
    }

    public function testTheReportRendersWithoutOpenPositions(): void
    {
        $this->request();

        self::assertResponseIsSuccessful();
    }

    public function testTheReportsRenderWhenOpenPositionsHaveNoAllocation(): void
    {
        $this->position('ZEROA', 'Zeroindustry', $this->user, 0.0);

        $this->request();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Zeroindustry', $this->content());

        $this->client->request('GET', self::POSITION_PATH);
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('ZEROA Corp', $this->content());
    }

    public function testTheReportsOnlyUseTheUsersOwnPositions(): void
    {
        $this->position('MINE1', 'Mineindustry', $this->user, 1000.0);
        $this->position('THEIR', 'Theirindustry', $this->otherUser, 5000.0);

        // The other user opens the reports first, so their data is cached.
        $this->request($this->otherUser);
        self::assertStringContainsString('Theirindustry', $this->content());
        $this->client->request('GET', self::POSITION_PATH);
        self::assertStringContainsString('THEIR Corp', $this->content());

        $this->request();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Mineindustry', $this->content());
        self::assertStringNotContainsString('Theirindustry', $this->content());

        $this->client->request('GET', self::POSITION_PATH);
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('MINE1 Corp', $this->content());
        self::assertStringNotContainsString('THEIR Corp', $this->content());
    }

    private function request(?User $user = null): void
    {
        $this->client->loginUser($user ?? $this->user);
        static::getContainer()->get('doctrine')->getManager()->clear();
        $this->client->request('GET', self::PATH);
    }

    private function content(): string
    {
        return (string) $this->client->getResponse()->getContent();
    }

    private function position(string $symbol, string $industry, User $user, float $allocation): void
    {
        PositionFactory::createOne([
            'ticker' => TickerFactory::createOne([
                'symbol' => $symbol,
                'fullname' => $symbol . ' Corp',
                'branch' => BranchFactory::createOne(['label' => $industry]),
            ]),
            'currency' => CurrencyFactory::createOne(['symbol' => 'EUR']),
            'user' => $user,
            'allocation' => $allocation,
            'amount' => 10.0,
            'closed' => false,
            'ignore_for_dividend' => false,
        ]);
    }

    private function user(int $id): User
    {
        $user = static::getContainer()->get(UserRepository::class)->find($id);
        self::assertInstanceOf(User::class, $user);

        return $user;
    }
}
