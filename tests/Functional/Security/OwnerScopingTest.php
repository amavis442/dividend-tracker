<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\ImportFiles;
use App\Entity\MonthlySummary;
use App\Entity\Trading212PieInstrument;
use App\Entity\Trading212PieMetaData;
use App\Entity\User;
use App\EventListener\OnRequestListener;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Monthly summaries, import records and Trading212 pie data belong to one user (#74).
 */
#[Group('security')]
final class OwnerScopingTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const SUMMARY_PATH = '/nl/dashboard/monthly/summary';
    private const SHARED_FILE_NAME = 'trading212-2026-10.csv';

    private KernelBrowser $client;
    private Connection $connection;
    private int $aliceId;
    private int $bobId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->connection = static::getContainer()->get(Connection::class);
        // Normal users, not admins: they manage their own data.
        $this->aliceId = UserFactory::createOne(['email' => 'alice@example.test', 'roles' => ['ROLE_USER']])->getId();
        $this->bobId = UserFactory::createOne(['email' => 'bob@example.test', 'roles' => ['ROLE_USER']])->getId();
    }

    public function testAUserCreatesAndSeesOnlyTheirOwnMonthlySummaries(): void
    {
        $bobSummary = $this->monthlySummary($this->bobId, 4321.0);
        $this->login($this->aliceId);

        $crawler = $this->client->request('GET', self::SUMMARY_PATH . '/new');
        self::assertResponseIsSuccessful();
        $form = $crawler->filter('form[name="monthly_summary"]')->form([
            'monthly_summary[acDate]' => '2026-10-31',
            'monthly_summary[accountValue]' => '1234',
        ]);
        $this->client->submit($form);
        self::assertResponseRedirects();
        self::assertSame(
            $this->aliceId,
            (int) $this->connection->fetchOne('select user_id from monthly_summary where account_value = 1234')
        );

        $this->client->request('GET', self::SUMMARY_PATH);
        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('1234', $content);
        self::assertStringNotContainsString('4321', $content);

        foreach (['', '/edit'] as $suffix) {
            $this->client->request('GET', self::SUMMARY_PATH . '/' . $bobSummary . $suffix);
            self::assertResponseStatusCodeSame(404, 'bob summary' . $suffix);
        }
    }

    public function testImportRecordsAreSeparatedPerUser(): void
    {
        // The same file name may be imported by two users.
        $this->importRecord($this->aliceId, self::SHARED_FILE_NAME);
        $this->importRecord($this->bobId, self::SHARED_FILE_NAME);
        $this->importRecord($this->bobId, 'only-bob.csv');

        $manager = $this->managerFor($this->aliceId);
        $records = $manager->getRepository(ImportFiles::class)->findAll();

        self::assertCount(1, $records);
        self::assertSame($this->aliceId, $records[0]->getOwner()?->getId());
        self::assertNull($manager->getRepository(ImportFiles::class)->findOneBy(['name' => 'only-bob.csv']));
    }

    public function testTrading212PieDataIsSeparatedPerUser(): void
    {
        $aliceMetaData = $this->pieMetaData($this->aliceId, 11);
        $bobMetaData = $this->pieMetaData($this->bobId, 22);
        $this->pieInstrument($aliceMetaData, 11, 'ALICE_US_EQ');
        $this->pieInstrument($bobMetaData, 22, 'BOB_US_EQ');

        $manager = $this->managerFor($this->aliceId);

        $metaData = $manager->getRepository(Trading212PieMetaData::class)->findAll();
        self::assertSame([11], array_map(static fn ($row) => $row->getTrading212PieId(), $metaData));

        $instruments = $manager->getRepository(Trading212PieInstrument::class)->findAll();
        self::assertSame(['ALICE_US_EQ'], array_map(static fn ($row) => $row->getTickerName(), $instruments));
    }

    /**
     * An entity manager with the user filter enabled, as during a request by this user.
     */
    private function managerFor(int $userId): EntityManagerInterface
    {
        $manager = static::getContainer()->get('doctrine')->getManager();
        $manager->clear();
        $manager->getFilters()->enable(OnRequestListener::USER_FILTER)->setParameter('userID', $userId);

        return $manager;
    }

    private function login(int $userId): void
    {
        $user = static::getContainer()->get(UserRepository::class)->find($userId);
        self::assertInstanceOf(User::class, $user);
        $this->client->loginUser($user);
        static::getContainer()->get('doctrine')->getManager()->clear();
    }

    private function monthlySummary(int $userId, float $accountValue): int
    {
        $manager = static::getContainer()->get('doctrine')->getManager();
        $summary = (new MonthlySummary())
            ->setUser($manager->getReference(User::class, $userId))
            ->setAcDate(new DateTime('2026-09-30'))
            ->setAccountValue($accountValue)
            ->setUuid(Uuid::v4());
        $manager->persist($summary);
        $manager->flush();

        return (int) $summary->getId();
    }

    private function importRecord(int $userId, string $name): void
    {
        $this->connection->executeStatement(
            "insert into import_files (id, name, created_at, owner_id)
             values (nextval('import_files_id_seq'), ?, now(), ?)",
            [$name, $userId]
        );
    }

    private function pieMetaData(int $userId, int $trading212PieId): int
    {
        $this->connection->executeStatement(
            "insert into trading212_pie_meta_data (id, trading212_pie_id, user_id, raw, created_at)
             values (nextval('trading212_pie_meta_data_id_seq'), ?, ?, '[]', now())",
            [$trading212PieId, $userId]
        );

        return (int) $this->connection->fetchOne('select max(id) from trading212_pie_meta_data');
    }

    private function pieInstrument(int $metaDataId, int $trading212PieId, string $tickerName): void
    {
        $this->connection->executeStatement(
            "insert into trading212_pie_instrument
                (id, ticker_name, raw, created_at, trading212_pie_id, trading212_pie_meta_data_id)
             values (nextval('trading212_pie_instrument_id_seq'), ?, '[]', now(), ?, ?)",
            [$tickerName, $trading212PieId, $metaDataId]
        );
    }
}
