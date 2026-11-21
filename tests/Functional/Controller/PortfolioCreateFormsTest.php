<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Factory\CurrencyFactory;
use App\Factory\PositionFactory;
use App\Factory\TickerFactory;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * The corporate action and dividend create forms on the portfolio detail page (#83).
 */
#[Group('controller')]
final class PortfolioCreateFormsTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const PREFIX = '/nl/dashboard/portfolio';

    private KernelBrowser $client;
    private Connection $connection;
    private int $tickerId;
    private int $currencyId;
    private int $positionId;
    private int $otherPositionId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->connection = static::getContainer()->get(Connection::class);

        $adminId = UserFactory::createOne(['email' => 'admin@example.test', 'roles' => ['ROLE_USER', 'ROLE_ADMIN']])
            ->getId();
        $otherId = UserFactory::createOne(['email' => 'other@example.test', 'roles' => ['ROLE_USER', 'ROLE_ADMIN']])
            ->getId();
        $currency = CurrencyFactory::createOne(['symbol' => 'USD']);
        $ticker = TickerFactory::createOne(['symbol' => 'AAPL', 'fullname' => 'Apple', 'currency' => $currency]);
        $this->tickerId = $ticker->getId();
        $this->currencyId = $currency->getId();
        $this->positionId = PositionFactory::createOne([
            'ticker' => $ticker,
            'currency' => $currency,
            'user' => $this->user($adminId),
        ])->getId();
        $this->otherPositionId = PositionFactory::createOne([
            'ticker' => $ticker,
            'currency' => $currency,
            'user' => $this->user($otherId),
        ])->getId();

        $this->client->loginUser($this->user($adminId));
        // Start requests with an empty identity map, as in production, so the user filter applies.
        static::getContainer()->get('doctrine')->getManager()->clear();
    }

    public function testTheCorporateActionFormRendersAndCanBeSubmitted(): void
    {
        $crawler = $this->client->request('GET', $this->corporateActionPath($this->positionId));
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('turbo-frame#add-corporateaction form[name="corporate_action"]');

        $form = $crawler->filter('form[name="corporate_action"]')->form([
            'corporate_action[type]' => 'split',
            'corporate_action[eventDate]' => '2026-07-01',
            'corporate_action[ratio]' => '2',
            'corporate_action[ticker]' => (string) $this->tickerId,
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertSame(
            ['type' => 'split', 'ticker_id' => $this->tickerId],
            $this->connection->fetchAssociative('select type, ticker_id from corporate_action')
        );
    }

    public function testTheDividendFormRendersAndCanBeSubmitted(): void
    {
        $crawler = $this->client->request('GET', $this->dividendPath($this->positionId));
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('turbo-frame#add-dividend form[name="calendar"]');

        $form = $crawler->filter('form[name="calendar"]')->form([
            'calendar[ex_dividend_date]' => '2026-06-01',
            'calendar[payment_date]' => '2026-06-15',
            'calendar[currency]' => (string) $this->currencyId,
            'calendar[cash_amount]' => '0.26',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertSame(
            ['ticker_id' => $this->tickerId, 'cash_amount' => 0.26],
            $this->connection->fetchAssociative('select ticker_id, cash_amount from calendar')
        );
    }

    public function testAnotherUsersPositionCannotBeUsed(): void
    {
        $paths = [
            $this->corporateActionPath($this->otherPositionId),
            $this->dividendPath($this->otherPositionId),
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            self::assertResponseStatusCodeSame(404, $path);
        }
    }

    private function corporateActionPath(int $positionId): string
    {
        return self::PREFIX . '/show/corporate/action/create/' . $positionId;
    }

    private function dividendPath(int $positionId): string
    {
        return self::PREFIX . '/create_dividend/' . $positionId;
    }

    private function user(int $id): User
    {
        $user = static::getContainer()->get(UserRepository::class)->find($id);
        self::assertInstanceOf(User::class, $user);

        return $user;
    }
}
