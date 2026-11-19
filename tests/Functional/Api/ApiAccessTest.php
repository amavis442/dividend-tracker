<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\User;
use App\Factory\CurrencyFactory;
use App\Factory\PaymentFactory;
use App\Factory\PositionFactory;
use App\Factory\TickerFactory;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[Group('api')]
final class ApiAccessTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private KernelBrowser $client;
    private int $alice;
    private int $bob;
    private int $bobPosition;
    private int $bobPayment;
    private int $bobPie;
    private int $bobJournal;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->alice = UserFactory::createOne(['email' => 'alice@example.test'])->getId();
        $this->bob = UserFactory::createOne(['email' => 'bob@example.test'])->getId();
        $currency = CurrencyFactory::createOne(['symbol' => 'USD']);
        $ticker = TickerFactory::createOne(['symbol' => 'SHRD']);
        PositionFactory::createOne(['ticker' => $ticker, 'user' => $this->user($this->alice), 'currency' => $currency]);
        $position = PositionFactory::createOne([
            'ticker' => $ticker,
            'user' => $this->user($this->bob),
            'currency' => $currency,
            'allocation' => 7777.77,
        ]);
        $this->bobPosition = $position->getId();
        $this->bobPayment = PaymentFactory::new()
            ->instantiateWith(Instantiator::withConstructor()->alwaysForce())
            ->create([
                'user' => $this->user($this->bob),
                'position' => $position,
                'ticker' => $ticker,
                'currency' => $currency,
                'dividend' => 55.55,
                'createdAt' => new \DateTimeImmutable(),
            ])->getId();

        $connection = static::getContainer()->get(Connection::class);
        $connection->executeStatement(
            "insert into pie (id, label, user_id) values (nextval('pie_id_seq'), 'Bob pie', ?)",
            [$this->bob]
        );
        $this->bobPie = (int) $connection->fetchOne('select max(id) from pie');

        $this->loginAs($this->bob);
        $this->api('POST', '/api/journals', ['title' => 'Bob journal', 'content' => 'Bob note']);
        self::assertResponseStatusCodeSame(201);
        $this->bobJournal = (int) basename((string) $this->client->getResponse()->headers->get('Location'));
    }

    public function testAnotherUsersObjectsCannotBeRead(): void
    {
        $this->loginAs($this->alice);

        foreach ([
            '/api/positions/' . $this->bobPosition,
            '/api/payments/' . $this->bobPayment,
            '/api/pies/' . $this->bobPie,
            '/api/journals/' . $this->bobJournal,
        ] as $path) {
            $this->api('GET', $path);
            $this->assertDenied($path);
        }
    }

    public function testAnotherUsersObjectsCannotBeChanged(): void
    {
        $this->loginAs($this->alice);

        $this->api('PATCH', '/api/pies/' . $this->bobPie, ['label' => 'Changed']);
        $this->assertDenied('patch pie');
        $this->api('PUT', '/api/journals/' . $this->bobJournal, ['title' => 'Changed', 'content' => 'Changed']);
        self::assertResponseStatusCodeSame(405);
        $this->api('PATCH', '/api/journals/' . $this->bobJournal, ['content' => 'Changed']);
        $this->assertDenied('patch journal');
        $this->api('DELETE', '/api/pies/' . $this->bobPie);
        $this->assertDenied('delete pie');
        $this->api('DELETE', '/api/journals/' . $this->bobJournal);
        $this->assertDenied('delete journal');

        $connection = static::getContainer()->get(Connection::class);
        self::assertSame('Bob pie', $connection->fetchOne('select label from pie where id = ?', [$this->bobPie]));
        self::assertSame('Bob note', $connection->fetchOne('select content from journal where id = ?', [$this->bobJournal]));
    }

    public function testCollectionsOnlyContainOwnObjects(): void
    {
        $this->loginAs($this->alice);

        foreach (['/api/pies' => 'Bob pie', '/api/journals' => 'Bob note', '/api/positions' => '7777.77', '/api/payments' => '55.55'] as $path => $bobValue) {
            $this->api('GET', $path);
            self::assertResponseIsSuccessful();
            self::assertStringNotContainsString($bobValue, (string) $this->client->getResponse()->getContent(), $path);
        }
    }

    public function testOwnObjectsRemainAvailable(): void
    {
        $this->loginAs($this->bob);

        foreach (['/api/positions/' . $this->bobPosition, '/api/payments/' . $this->bobPayment, '/api/pies/' . $this->bobPie, '/api/journals/' . $this->bobJournal] as $path) {
            $this->api('GET', $path);
            self::assertResponseIsSuccessful($path);
        }

        $this->api('PATCH', '/api/journals/' . $this->bobJournal, ['content' => 'Updated note']);
        self::assertResponseIsSuccessful();
    }

    public function testAnotherAccountCannotBeRead(): void
    {
        $this->loginAs($this->alice);

        $this->api('GET', '/api/users/' . $this->bob);

        $this->assertDenied('user');
        self::assertStringNotContainsString('bob@example.test', (string) $this->client->getResponse()->getContent());
    }

    public function testAnAccountCannotBeReadAnonymously(): void
    {
        $this->client->restart();

        $this->api('GET', '/api/users/' . $this->bob);

        self::assertContains($this->client->getResponse()->getStatusCode(), [302, 401], 'login required');
        self::assertStringNotContainsString('bob@example.test', (string) $this->client->getResponse()->getContent());
    }

    public function testPasswordsAreNeverReturned(): void
    {
        $this->loginAs($this->alice);
        $this->api('GET', '/api/users/' . $this->alice);
        self::assertResponseIsSuccessful();
        $this->assertNoSecrets();

        $this->client->restart();
        $this->api('POST', '/api/users', ['email' => 'new@example.test', 'password' => 'A-long-passphrase-1']);
        self::assertResponseStatusCodeSame(201);
        $this->assertNoSecrets();
    }

    private function assertNoSecrets(): void
    {
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('"password"', $content);
        self::assertStringNotContainsString('apiToken', $content);
        self::assertStringNotContainsString('$2y$', $content);
    }

    private function assertDenied(string $what): void
    {
        self::assertContains($this->client->getResponse()->getStatusCode(), [403, 404], $what);
    }

    private function loginAs(int $userId): void
    {
        $this->client->loginUser($this->user($userId));
    }

    private function user(int $id): User
    {
        $user = static::getContainer()->get(UserRepository::class)->find($id);
        self::assertInstanceOf(User::class, $user);

        return $user;
    }

    /**
     * @param array<string, mixed>|null $json
     */
    private function api(string $method, string $path, ?array $json = null): void
    {
        $contentType = $method === 'PATCH' ? 'application/merge-patch+json' : 'application/ld+json';
        $this->client->request(
            $method,
            $path,
            server: ['CONTENT_TYPE' => $contentType, 'HTTP_ACCEPT' => 'application/ld+json'],
            content: $json === null ? null : json_encode($json, JSON_THROW_ON_ERROR)
        );
    }
}
