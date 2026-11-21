<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\User;
use App\Factory\BranchFactory;
use App\Factory\CalendarFactory;
use App\Factory\CurrencyFactory;
use App\Factory\PositionFactory;
use App\Factory\TaxFactory;
use App\Factory\TickerFactory;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[Group('security')]
final class AdminAccessTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const NL = '/nl/dashboard';

    private KernelBrowser $client;

    /** @var array<string, int> */
    private array $ids = [];

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $this->ids = [
            'user' => (int) $user->getId(),
            'admin' => (int) ($admin = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]))->getId(),
            'ticker' => (int) TickerFactory::createOne()->getId(),
            'currency' => (int) CurrencyFactory::createOne()->getId(),
            'tax' => (int) TaxFactory::createOne()->getId(),
            'branch' => (int) BranchFactory::createOne()->getId(),
            'calendar' => (int) CalendarFactory::createOne(['cashAmount' => 0.5])->getId(),
            'userPosition' => (int) PositionFactory::createOne(['user' => $user])->getId(),
            'adminPosition' => (int) PositionFactory::createOne(['user' => $admin])->getId(),
        ];
    }

    /**
     * @return list<array{string, string}>
     */
    private function adminOnlyPages(string $who): array
    {
        return [
            ['GET', self::NL . '/admin/ticker/'],
            ['GET', self::NL . '/admin/ticker/' . $this->ids['ticker'] . '/edit'],
            ['GET', self::NL . '/admin/ticker/symbol'],
            ['GET', self::NL . '/admin/apikeys'],
            ['GET', self::NL . '/admin/globalx/'],
            ['GET', '/dashboard/portfolio/admin'],
            ['GET', self::NL . '/register'],
            ['GET', self::NL . '/currency/create'],
            ['GET', self::NL . '/currency/' . $this->ids['currency'] . '/edit'],
            ['POST', self::NL . '/currency/delete/' . $this->ids['currency']],
            ['GET', self::NL . '/tax/create'],
            ['GET', self::NL . '/tax/' . $this->ids['tax'] . '/edit'],
            ['POST', self::NL . '/tax/delete/' . $this->ids['tax']],
            ['GET', self::NL . '/branch/create'],
            ['GET', self::NL . '/branch/' . $this->ids['branch'] . '/edit'],
            ['POST', self::NL . '/branch/delete/' . $this->ids['branch']],
            ['GET', self::NL . '/calendar/create'],
            ['GET', self::NL . '/calendar/' . $this->ids['calendar'] . '/edit'],
            ['POST', self::NL . '/calendar/delete/' . $this->ids['calendar']],
            ['GET', self::NL . '/corporate/action/new'],
            ['GET', self::NL . '/portfolio/show/corporate/action/create/' . $this->ids[$who . 'Position']],
            ['GET', self::NL . '/research/create/' . $this->ids['ticker']],
            ['GET', self::NL . '/portfolio/create_dividend/' . $this->ids[$who . 'Position']],
            ['POST', self::NL . '/portfolio/delete_dividend/' . $this->ids['calendar'] . '/' . $this->ids[$who . 'Position']],
        ];
    }

    public function testANormalUserCannotOpenAdminPagesOrChangeSharedData(): void
    {
        $this->loginAs('user');

        foreach ($this->adminOnlyPages('user') as [$method, $path]) {
            $this->client->request($method, $path);
            self::assertResponseStatusCodeSame(403, $method . ' ' . $path);
        }
    }

    public function testANormalUserCanStillReadSharedData(): void
    {
        $this->loginAs('user');

        foreach ([
            self::NL . '/currency/',
            self::NL . '/currency/' . $this->ids['currency'],
            self::NL . '/tax/',
            self::NL . '/branch/',
            self::NL . '/calendar/',
        ] as $path) {
            $this->client->request('GET', $path);
            self::assertResponseIsSuccessful($path);
        }
    }

    public function testAnAdminCanOpenAdminPages(): void
    {
        $this->loginAs('admin');

        foreach ($this->adminOnlyPages('admin') as [$method, $path]) {
            if ($method !== 'GET') {
                continue;
            }
            $this->client->request($method, $path);
            self::assertResponseIsSuccessful($path);
        }
    }

    public function testAnonymousVisitorsAreSentToTheLogin(): void
    {
        foreach (['/dashboard/portfolio/admin', '/prices', '/stock_prices/AAPL'] as $path) {
            $this->client->request('GET', $path);
            self::assertResponseRedirects(null, null, $path);
            self::assertStringContainsString(
                '/login',
                (string) $this->client->getResponse()->headers->get('Location'),
                $path
            );
        }
    }

    public function testOnlyAnAdminCanCreateUsersThroughTheApi(): void
    {
        $this->postUser('anonymous@example.test');
        self::assertContains($this->client->getResponse()->getStatusCode(), [302, 401]);

        $this->loginAs('user');
        $this->postUser('by-user@example.test');
        self::assertResponseStatusCodeSame(403);

        $this->loginAs('admin');
        $this->postUser('by-admin@example.test');
        self::assertResponseStatusCodeSame(201);
    }

    public function testAnAdminSeesTheInviteLinkAndAConfirmation(): void
    {
        $this->loginAs('admin');

        $crawler = $this->client->request('GET', self::NL . '/register');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a[href$="/nl/dashboard/register"]');

        $form = $crawler->selectButton('Register')->form();
        $form['registration_form[email]'] = 'invited@example.test';
        $form['registration_form[plainPassword]'] = 'Correct-Horse-Battery-9';
        $form['registration_form[agreeTerms]']->tick();
        $this->client->submit($form);
        self::assertResponseRedirects();
        $this->client->followRedirect();

        self::assertSelectorTextContains('[role="status"]', 'Account created.');
    }

    private function postUser(string $email): void
    {
        $this->client->request(
            'POST',
            '/api/users',
            server: ['CONTENT_TYPE' => 'application/ld+json', 'HTTP_ACCEPT' => 'application/ld+json'],
            content: json_encode(['email' => $email, 'password' => 'Correct-Horse-Battery-9'], JSON_THROW_ON_ERROR)
        );
    }

    private function loginAs(string $who): void
    {
        $user = static::getContainer()->get(UserRepository::class)->find($this->ids[$who]);
        self::assertInstanceOf(User::class, $user);
        $this->client->loginUser($user);
    }
}
