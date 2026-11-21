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
use DateTime;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * The closed positions pages (#85).
 */
#[Group('controller')]
final class ClosedPositionControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const PREFIX = '/nl/dashboard/closed/position';

    private KernelBrowser $client;
    private int $olderClosedId;
    private int $newerClosedId;
    private int $otherUsersClosedId;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $userId = UserFactory::createOne(['email' => 'closed@example.test'])->getId();
        $otherId = UserFactory::createOne(['email' => 'other@example.test'])->getId();
        $user = $this->user($userId);
        $currency = CurrencyFactory::createOne(['symbol' => 'EUR']);
        $branch = BranchFactory::createOne(['label' => 'Energy']);

        $this->olderClosedId = $this->position('OLDCL', $user, $currency, $branch, '2024-03-01', 12.5)->getId();
        $this->newerClosedId = $this->position('NEWCL', $user, $currency, $branch, '2025-06-01', -4.25)->getId();
        $this->position('OPENP', $user, $currency, $branch, null, 0.0);
        $other = $this->user($otherId);
        $this->otherUsersClosedId = $this->position('OTHER', $other, $currency, $branch, '2025-01-01', 1.0)->getId();

        $this->client->loginUser($user);
        static::getContainer()->get('doctrine')->getManager()->clear();
    }

    public function testTheListShowsOwnClosedPositionsNewestFirst(): void
    {
        $this->client->request('GET', self::PREFIX . '/list/1/desc');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('OPENP', $content, 'open positions are not listed');
        self::assertStringNotContainsString('OTHER', $content, "another user's positions are not listed");
        self::assertLessThan(strpos($content, 'OLDCL'), strpos($content, 'NEWCL'));
        self::assertSelectorTextContains('body', '12,50');
        self::assertSelectorTextContains('body', '-4,25');
    }

    public function testTheListCanBeSortedOldestFirst(): void
    {
        $this->client->request('GET', self::PREFIX . '/list/1/asc');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertLessThan(strpos($content, 'NEWCL'), strpos($content, 'OLDCL'));
    }

    public function testTheMenuLinksToTheList(): void
    {
        $crawler = $this->client->request('GET', '/nl/dashboard/portfolio/');

        $link = $crawler->filter('nav a[href$="' . self::PREFIX . '/list"]');
        self::assertCount(1, $link);
        $this->client->click($link->link());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'NEWCL');
    }

    public function testTheDetailAndEditPagesRender(): void
    {
        $this->client->request('GET', self::PREFIX . '/' . $this->olderClosedId);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'OLDCL');
        self::assertSelectorTextContains('body', '01-03-2024');

        $this->client->request('GET', self::PREFIX . '/' . $this->olderClosedId . '/edit');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="position"]');
    }

    public function testAnotherUsersClosedPositionCannotBeOpened(): void
    {
        foreach (['', '/edit'] as $suffix) {
            $this->client->request('GET', self::PREFIX . '/' . $this->otherUsersClosedId . $suffix);
            self::assertResponseStatusCodeSame(404, $suffix);
        }
    }

    private function position(
        string $symbol,
        User $user,
        mixed $currency,
        mixed $branch,
        ?string $closedAt,
        float $profit
    ): mixed {
        return PositionFactory::createOne([
            'ticker' => TickerFactory::createOne([
                'symbol' => $symbol,
                'fullname' => $symbol . ' NV',
                'branch' => $branch,
            ]),
            'currency' => $currency,
            'user' => $user,
            'closed' => $closedAt !== null,
            'closedAt' => $closedAt !== null ? new DateTime($closedAt) : null,
            'profit' => $profit,
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
