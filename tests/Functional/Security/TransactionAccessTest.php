<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\Transaction;
use App\Entity\User;
use App\Factory\CurrencyFactory;
use App\Factory\PositionFactory;
use App\Factory\TickerFactory;
use App\Factory\TransactionFactory;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[Group('security')]
final class TransactionAccessTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const BOB_AMOUNT = 3.3333;
    private const WEB = '/nl/dashboard/transaction';

    private KernelBrowser $client;
    private int $alice;
    private int $bob;
    private int $bobPosition;
    private int $bobTransaction;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->alice = UserFactory::createOne()->getId();
        $this->bob = UserFactory::createOne()->getId();
        $currency = CurrencyFactory::createOne(['symbol' => 'USD']);
        $ticker = TickerFactory::createOne();
        $position = PositionFactory::createOne([
            'ticker' => $ticker,
            'user' => $this->user($this->bob),
            'currency' => $currency,
            'closed' => false,
        ]);
        $this->bobPosition = $position->getId();
        $this->bobTransaction = TransactionFactory::new()
            ->instantiateWith(Instantiator::withConstructor()->alwaysForce())
            ->create([
                'position' => $position,
                'currency' => $currency,
                'amount' => self::BOB_AMOUNT,
                'side' => Transaction::BUY,
                'transactionDate' => new \DateTime('2025-01-01'),
                'createdAt' => new \DateTimeImmutable(),
            ])->getId();
    }

    public function testTheApiHidesAnotherUsersTransactions(): void
    {
        $this->loginAs($this->alice);

        $this->api('GET', '/api/transactions/' . $this->bobTransaction);
        self::assertContains($this->statusCode(), [403, 404]);

        $this->api('GET', '/api/transactions');
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString((string) self::BOB_AMOUNT, $this->content());
    }

    public function testTheApiDoesNotCreateTransactions(): void
    {
        $this->loginAs($this->alice);

        $this->api('POST', '/api/transactions', [
            'position' => '/api/positions/' . $this->bobPosition,
            'amount' => 1.0,
            'price' => 1.0,
            'side' => Transaction::BUY,
            'transactionDate' => '2025-02-01',
        ]);

        self::assertResponseStatusCodeSame(405);
        self::assertSame(1, $this->transactionCount());
    }

    public function testSearchIsNotTakenForATransactionId(): void
    {
        $this->loginAs($this->bob);

        $this->client->request('POST', self::WEB . '/search', ['searchCriteria' => 'X']);

        self::assertResponseRedirects();
    }

    public function testTheApiShowsOwnTransactions(): void
    {
        $this->loginAs($this->bob);

        $this->api('GET', '/api/transactions/' . $this->bobTransaction);
        self::assertResponseIsSuccessful();

        $this->api('GET', '/api/transactions');
        self::assertStringContainsString((string) self::BOB_AMOUNT, $this->content());
    }

    public function testTheWebPagesRefuseAnotherUsersTransactions(): void
    {
        $this->loginAs($this->alice);

        foreach ([
            ['GET', self::WEB . '/' . $this->bobTransaction],
            ['GET', self::WEB . '/' . $this->bobTransaction . '/edit/0'],
            ['POST', self::WEB . '/' . $this->bobTransaction],
            ['GET', self::WEB . '/create/' . $this->bobPosition],
        ] as [$method, $path]) {
            $this->client->request($method, $path);
            self::assertContains($this->statusCode(), [403, 404], $method . ' ' . $path);
        }

        self::assertSame(1, $this->transactionCount());
    }

    public function testTheCreatePageNeedsAnExistingPosition(): void
    {
        $this->loginAs($this->alice);

        $this->client->request('GET', self::WEB . '/create/999999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testTheWebPagesShowOwnTransactions(): void
    {
        $this->loginAs($this->bob);

        $this->client->request('GET', self::WEB . '/' . $this->bobTransaction . '/edit/0');

        self::assertResponseIsSuccessful();
    }

    private function transactionCount(): int
    {
        // Counted in SQL: the user filter would hide other users' transactions.
        return (int) static::getContainer()->get(Connection::class)->fetchOne('select count(*) from transaction');
    }

    private function statusCode(): int
    {
        return $this->client->getResponse()->getStatusCode();
    }

    private function content(): string
    {
        return (string) $this->client->getResponse()->getContent();
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
        $this->client->request(
            $method,
            $path,
            server: ['CONTENT_TYPE' => 'application/ld+json', 'HTTP_ACCEPT' => 'application/ld+json'],
            content: $json === null ? null : json_encode($json, JSON_THROW_ON_ERROR)
        );
    }
}
