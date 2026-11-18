<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Ticker;
use App\Entity\User;
use App\Factory\CalendarFactory;
use App\Factory\PositionFactory;
use App\Factory\TickerFactory;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[Group('controller')]
final class CalendarControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const PATH = '/nl/dashboard/calendar/';
    private const FIELD = 'ticker_autocomplete[ticker]';

    private KernelBrowser $client;
    private User $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $email = UserFactory::createOne()->getEmail();
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        self::assertInstanceOf(User::class, $user);
        $this->user = $user;
        $this->client->loginUser($user);
    }

    public function testIndexListsDividendsOfOpenPositions(): void
    {
        $this->heldTicker('Alpha Income');
        $this->calendarFor(TickerFactory::createOne(['fullname' => 'Not Held']));

        $this->client->request('GET', self::PATH);

        self::assertResponseIsSuccessful();
        self::assertAnySelectorTextContains('turbo-frame#calendar-results td', 'Alpha Income');
        self::assertAnySelectorTextNotContains('turbo-frame#calendar-results td', 'Not Held');
    }

    public function testIndexRendersWithoutDividends(): void
    {
        $this->client->request('GET', self::PATH);

        self::assertResponseIsSuccessful();
        self::assertAnySelectorTextContains('turbo-frame#calendar-results td', 'no records found');
    }

    public function testAnUnknownSortFallsBackToTheDefault(): void
    {
        $this->heldTicker('Alpha Income');

        $this->client->request('GET', self::PATH . '?sort=nonsense&orderBy=nonsense');

        self::assertResponseIsSuccessful();
        self::assertAnySelectorTextContains('turbo-frame#calendar-results td', 'Alpha Income');
    }

    public function testTheSymbolSortLinkWorks(): void
    {
        $this->heldTicker('Alpha Income');

        $crawler = $this->client->request('GET', self::PATH);
        $link = $crawler->filter('turbo-frame#calendar-results th a')->first()->link();
        self::assertStringContainsString('sort=symbol', $link->getUri());

        $this->client->click($link);

        self::assertResponseIsSuccessful();
    }

    public function testSearchFiltersOnTickerUntilItIsCleared(): void
    {
        $alpha = $this->heldTicker('Alpha Income');
        $this->heldTicker('Beta Income');

        $this->search((string) $alpha->getId());

        self::assertAnySelectorTextContains('turbo-frame#calendar-results td', 'Alpha Income');
        self::assertAnySelectorTextNotContains('turbo-frame#calendar-results td', 'Beta Income');

        $this->client->request('GET', self::PATH);
        self::assertAnySelectorTextNotContains('turbo-frame#calendar-results td', 'Beta Income');

        $this->search('');

        self::assertAnySelectorTextContains('turbo-frame#calendar-results td', 'Beta Income');
    }

    public function testSearchShowsATickerWithoutPosition(): void
    {
        $ticker = TickerFactory::createOne(['fullname' => 'Not Held']);
        $this->calendarFor($ticker);

        $this->search((string) $ticker->getId());

        self::assertAnySelectorTextContains('turbo-frame#calendar-results td', 'Not Held');
    }

    private function search(string $tickerId): void
    {
        $crawler = $this->client->request('GET', self::PATH);
        $form = $crawler->filter('form[data-turbo-frame="calendar-results"]')->form();
        $form->disableValidation();
        $form[self::FIELD] = $tickerId;

        $this->client->submit($form);

        self::assertResponseStatusCodeSame(303);
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    private function heldTicker(string $fullname): Ticker
    {
        $ticker = TickerFactory::createOne(['fullname' => $fullname]);
        PositionFactory::createOne(['ticker' => $ticker, 'user' => $this->user, 'closed' => false]);
        $this->calendarFor($ticker);

        return $ticker;
    }

    private function calendarFor(Ticker $ticker): void
    {
        CalendarFactory::new()
            ->instantiateWith(Instantiator::withConstructor()->alwaysForce())
            ->create(['ticker' => $ticker, 'createdAt' => new \DateTimeImmutable()]);
    }
}
