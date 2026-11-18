<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Currency;
use App\Entity\Ticker;
use App\Entity\User;
use App\Factory\CalendarFactory;
use App\Factory\CurrencyFactory;
use App\Factory\DividendMonthFactory;
use App\Factory\PositionFactory;
use App\Factory\TaxFactory;
use App\Factory\TickerFactory;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use App\Service\ExchangeRate\WisselkoersNlExchangeRateService;
use App\Tests\Support\StubExchangeRates;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[Group('controller')]
final class YieldControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const PATH = '/nl/dashboard/report/yield';
    private const PIE_PATH = '/nl/dashboard/report/pieyield';

    private KernelBrowser $client;
    private User $user;
    private Currency $currency;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        static::getContainer()->set(WisselkoersNlExchangeRateService::class, new StubExchangeRates());

        $email = UserFactory::createOne()->getEmail();
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        self::assertInstanceOf(User::class, $user);
        $this->user = $user;
        $this->currency = CurrencyFactory::createOne(['symbol' => 'USD', 'sign' => '$']);
        $this->client->loginUser($user);
    }

    public function testTheReportRendersWithoutPositions(): void
    {
        $this->client->request('GET', self::PATH);

        self::assertResponseIsSuccessful();
    }

    public function testThePieReportRendersWithoutPositions(): void
    {
        $this->client->request('GET', self::PIE_PATH);

        self::assertResponseIsSuccessful();
    }

    public function testTheReportListsAnOpenPosition(): void
    {
        $this->createPosition('YLD');

        $this->client->request('GET', self::PATH);

        self::assertResponseIsSuccessful();
        self::assertAnySelectorTextContains('td', 'YLD');
    }

    public function testTheChartIsRendered(): void
    {
        $this->createPosition('YLD');

        $this->client->request('GET', self::PATH);

        self::assertSelectorExists('canvas[data-controller~="chartjs-add-percentage"]');
    }

    public function testAPositionWithoutTransactionsStillHasAYield(): void
    {
        $this->createPosition('YLD');

        $crawler = $this->client->request('GET', self::PATH);

        self::assertStringContainsString('%', $crawler->filter('tbody tr')->first()->filter('td')->last()->text());
    }

    public function testAPositionWithoutTickerIsSkipped(): void
    {
        PositionFactory::createOne([
            'ticker' => null,
            'user' => $this->user,
            'currency' => $this->currency,
            'closed' => false,
            'ignore_for_dividend' => false,
        ]);

        $this->client->request('GET', self::PATH);

        self::assertResponseIsSuccessful();
    }

    public function testAnAccentedNameKeepsTheChartValid(): void
    {
        $this->createPosition('SOC', fullname: 'Société Générale');

        $crawler = $this->client->request('GET', self::PATH);
        $view = (string) $crawler->filter('canvas')->attr('data-symfony--ux-chartjs--chart-view-value');

        self::assertStringContainsString('SOC', $view);
    }

    public function testTheCurrencySignIsShownEscaped(): void
    {
        $this->currency->setSign('&pound;');
        static::getContainer()->get(EntityManagerInterface::class)->flush();
        $this->createPosition('YLD');

        $this->client->request('GET', self::PATH);

        self::assertAnySelectorTextContains('td', '£0,50');
    }

    public function testAPositionWithoutAllocationDoesNotCrash(): void
    {
        $this->createPosition('YLD', allocation: 0.0);

        $this->client->request('GET', self::PATH);

        self::assertResponseIsSuccessful();
        self::assertAnySelectorTextContains('td', 'YLD');
    }

    public function testADividendWithoutCurrencyDoesNotCrash(): void
    {
        $ticker = $this->createPosition('YLD', withCalendar: false);
        $this->createCalendar($ticker, 0.5, '2026-03-15', withCurrency: false);

        $this->client->request('GET', self::PATH);

        self::assertResponseIsSuccessful();
        self::assertAnySelectorTextContains('td', 'YLD');
    }

    public function testAnUnknownSortFallsBackToTheSymbol(): void
    {
        $this->createPosition('BBB');
        $this->createPosition('AAA');

        $crawler = $this->client->request('GET', self::PATH . '?sort=nonsense&sortDirection=nonsense');

        self::assertResponseIsSuccessful();
        self::assertSame(['AAA', 'BBB'], $this->tickers($crawler));
    }

    public function testSortLinksStayOnTheReportAndToggleTheDirection(): void
    {
        $this->createPosition('YLD');

        $crawler = $this->client->request('GET', self::PATH);
        $links = $crawler->filter('thead a');

        self::assertCount(3, $links);
        foreach ($links->links() as $link) {
            self::assertStringContainsString(self::PATH . '?', $link->getUri());
        }
        self::assertStringContainsString('sort=symbol', $links->eq(0)->link()->getUri());
        self::assertStringContainsString('sortDirection=desc', $links->eq(0)->link()->getUri());
        self::assertStringContainsString('sortDirection=asc', $links->eq(1)->link()->getUri());
    }

    public function testDividendsSortNumerically(): void
    {
        $this->createPosition('BIG', cashAmount: 1.0);
        $this->createPosition('SMALL', cashAmount: 0.3333);

        $crawler = $this->client->request('GET', self::PATH . '?sort=dividend&sortDirection=asc');
        self::assertSame(['SMALL', 'BIG'], $this->tickers($crawler));

        $crawler = $this->client->request('GET', self::PATH . '?sort=dividend&sortDirection=desc');
        self::assertSame(['BIG', 'SMALL'], $this->tickers($crawler));
    }

    public function testTheLastDividendIsTheMostRecentOne(): void
    {
        $ticker = $this->createPosition('YLD', withCalendar: false);
        $this->createCalendar($ticker, 0.5, '2026-03-15');
        $this->createCalendar($ticker, 0.2, '2023-03-15');

        $this->client->request('GET', self::PATH);

        self::assertAnySelectorTextContains('td', '15-03-2026');
        self::assertAnySelectorTextNotContains('td', '15-03-2023');
    }

    public function testTheLastDividendIsTheLastPaidOne(): void
    {
        $paid = new \DateTimeImmutable('-1 month');
        $declared = new \DateTimeImmutable('+1 month');
        $ticker = $this->createPosition('YLD', withCalendar: false);
        $this->createCalendar($ticker, 0.5, $paid->format('Y-m-d'));
        $this->createCalendar($ticker, 0.8, $declared->format('Y-m-d'));

        $crawler = $this->client->request('GET', self::PATH);
        $cells = $crawler->filter('tbody tr')->first()->filter('td');

        self::assertStringContainsString('0,50', $cells->eq(2)->text());
        self::assertSame($paid->format('d-m-Y'), trim($cells->eq(3)->text()));
        self::assertStringContainsString('0,80', $cells->eq(4)->text());
    }

    public function testNoLastDividendIsShownBeforeTheFirstPayment(): void
    {
        $ticker = $this->createPosition('YLD', withCalendar: false);
        $this->createCalendar($ticker, 0.8, (new \DateTimeImmutable('+1 month'))->format('Y-m-d'));

        $crawler = $this->client->request('GET', self::PATH);
        $cells = $crawler->filter('tbody tr')->first()->filter('td');

        self::assertSame('-', trim($cells->eq(2)->text()));
        self::assertSame('-', trim($cells->eq(3)->text()));
    }

    public function testTheChartFollowsTheSelectedSort(): void
    {
        $this->createPosition('AAA', cashAmount: 0.25);
        $this->createPosition('ZZZ', cashAmount: 1.0);

        $crawler = $this->client->request('GET', self::PATH . '?sort=dividend&sortDirection=desc');
        $view = (string) $crawler->filter('canvas')->attr('data-symfony--ux-chartjs--chart-view-value');

        self::assertLessThan(strpos($view, '(AAA)'), strpos($view, '(ZZZ)'));
    }

    public function testThePieReportShowsTheLastPaidDividend(): void
    {
        $paid = new \DateTimeImmutable('-1 month');
        $declared = new \DateTimeImmutable('+1 month');
        $ticker = $this->createPosition('KEEP', withCalendar: false, forPieReport: true);
        $this->createCalendar($ticker, 0.5, $paid->format('Y-m-d'));
        $this->createCalendar($ticker, 0.8, $declared->format('Y-m-d'));

        $this->client->request('GET', self::PIE_PATH);
        $content = (string) $this->client->getResponse()->getContent();

        self::assertResponseIsSuccessful();
        self::assertStringContainsString($paid->format('d-m-Y'), $content);
        self::assertStringNotContainsString($declared->format('d-m-Y'), $content);
    }

    public function testThePieReportFindsAnOlderPayment(): void
    {
        $paid = new \DateTimeImmutable('-6 months');
        $ticker = $this->createPosition('KEEP', withCalendar: false, forPieReport: true);
        $this->createCalendar($ticker, 0.5, $paid->format('Y-m-d'));
        $this->createCalendar($ticker, 0.8, (new \DateTimeImmutable('+1 month'))->format('Y-m-d'));

        $this->client->request('GET', self::PIE_PATH);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            $paid->format('d-m-Y'),
            (string) $this->client->getResponse()->getContent()
        );
    }

    public function testThePieReportHandlesAPaymentWithoutCurrency(): void
    {
        $ticker = $this->createPosition('KEEP', withCalendar: false, forPieReport: true);
        $this->createCalendar(
            $ticker,
            0.5,
            (new \DateTimeImmutable('-1 month'))->format('Y-m-d'),
            withCurrency: false
        );

        $this->client->request('GET', self::PIE_PATH);

        self::assertResponseIsSuccessful();
    }

    public function testThePieReportSkipsIgnoredPositions(): void
    {
        $this->createPosition('KEEP', forPieReport: true);
        $this->createPosition('SKIP', ignored: true, forPieReport: true);

        $this->client->request('GET', self::PIE_PATH);

        self::assertResponseIsSuccessful();
        self::assertAnySelectorTextContains('td', 'KEEP');
        self::assertAnySelectorTextNotContains('td', 'SKIP');
    }

    /**
     * @return list<string>
     */
    private function tickers(Crawler $crawler): array
    {
        return $crawler->filter('tbody tr td:first-child span')->each(
            static fn(Crawler $cell): string => trim($cell->text())
        );
    }

    private function createPosition(
        string $symbol,
        float $allocation = 100.0,
        float $cashAmount = 0.5,
        bool $withCalendar = true,
        bool $ignored = false,
        bool $forPieReport = false,
        ?string $fullname = null,
    ): Ticker {
        $attributes = [
            'symbol' => $symbol,
            'fullname' => $fullname ?? $symbol . ' Income',
            'dividendMonths' => [DividendMonthFactory::createOne(['dividendMonth' => 3])],
        ];
        if ($forPieReport) {
            $attributes['currency'] = $this->currency;
            $attributes['tax'] = TaxFactory::new()
                ->instantiateWith(Instantiator::withConstructor()->alwaysForce())
                ->create(['createdAt' => new \DateTimeImmutable()]);
        }

        $ticker = TickerFactory::createOne($attributes);
        if ($withCalendar) {
            $this->createCalendar($ticker, $cashAmount, '2026-03-15');
        }

        PositionFactory::createOne([
            'ticker' => $ticker,
            'user' => $this->user,
            'currency' => $this->currency,
            'closed' => false,
            'ignore_for_dividend' => $ignored,
            'allocation' => $allocation,
            'amount' => 10.0,
            'price' => 10.0,
        ]);

        return $ticker;
    }

    private function createCalendar(
        Ticker $ticker,
        float $cashAmount,
        string $paymentDate,
        bool $withCurrency = true,
    ): void {
        CalendarFactory::new()
            ->instantiateWith(Instantiator::withConstructor()->alwaysForce())
            ->create([
                'ticker' => $ticker,
                'currency' => $withCurrency ? $this->currency : null,
                'cashAmount' => $cashAmount,
                'paymentDate' => new \DateTime($paymentDate),
                'createdAt' => new \DateTimeImmutable(),
            ]);
    }
}
