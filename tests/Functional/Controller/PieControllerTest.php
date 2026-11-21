<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[Group('controller')]
final class PieControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const TRADING212_PIE_ID = 4242;

    private KernelBrowser $client;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->connection = static::getContainer()->get(Connection::class);

        $userId = UserFactory::createOne(['email' => 'pie@example.test'])->getId();
        $user = static::getContainer()->get(UserRepository::class)->find($userId);
        self::assertInstanceOf(User::class, $user);
        $this->client->loginUser($user);
    }

    public function testAPieCanBeCreatedThroughTheWebForm(): void
    {
        $this->insertMetaData();

        $crawler = $this->client->request('GET', '/nl/dashboard/pie/create');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="pie"]')->form([
            'pie[label]' => 'Web pie',
            'pie[trading212PieId]' => (string) self::TRADING212_PIE_ID,
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects();
        $pieId = $this->connection->fetchOne('select id from pie where label = ?', ['Web pie']);
        self::assertNotFalse($pieId);
        self::assertSame(
            ['pie_id' => (int) $pieId, 'pie_name' => 'Web pie'],
            $this->metaDataLink()
        );
    }

    public function testAPieCanBeCreatedThroughTheApi(): void
    {
        $this->client->request(
            'POST',
            '/api/pies',
            server: ['CONTENT_TYPE' => 'application/ld+json', 'HTTP_ACCEPT' => 'application/ld+json'],
            content: json_encode(['label' => 'Api pie'], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(201);
        self::assertSame(1, (int) $this->connection->fetchOne("select count(*) from pie where label = 'Api pie'"));
    }

    private function insertMetaData(): void
    {
        $this->connection->executeStatement(
            "insert into trading212_pie_meta_data
                (id, trading212_pie_id, price_avg_invested_value, price_avg_value,
                 raw, created_at, gained, reinvested, in_cash)
             values (nextval('trading212_pie_meta_data_id_seq'), ?, 0, 0, '[]', now(), 0, 0, 0)",
            [self::TRADING212_PIE_ID]
        );
    }

    /**
     * @return array<string, mixed>|false
     */
    private function metaDataLink(): array|false
    {
        return $this->connection->fetchAssociative(
            'select pie_id, pie_name from trading212_pie_meta_data where trading212_pie_id = ?',
            [self::TRADING212_PIE_ID]
        );
    }
}
