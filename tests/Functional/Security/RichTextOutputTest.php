<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\User;
use App\Factory\TickerFactory;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[Group('security')]
final class RichTextOutputTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const MALICIOUS = '<p><strong>Kept</strong></p><script>alert("x")</script><img src="x" onerror="alert(1)">'
        . '<a href="https://example.test" style="position:fixed;inset:0;z-index:9999" class="fixed inset-0">Overlay</a>';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Ticker pages are admin pages.
        $email = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']])->getEmail();
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        self::assertInstanceOf(User::class, $user);
        $this->client->loginUser($user);
    }

    public function testJournalContentIsSanitized(): void
    {
        $this->client->request(
            'POST',
            '/api/journals',
            server: ['CONTENT_TYPE' => 'application/ld+json', 'HTTP_ACCEPT' => 'application/ld+json'],
            content: json_encode(['title' => 'Note', 'content' => self::MALICIOUS], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(201);
        $id = basename((string) $this->client->getResponse()->headers->get('Location'));

        $this->client->request('GET', '/nl/dashboard/journal/' . $id);

        $this->assertSanitized();
        $this->client->request('GET', '/nl/dashboard/journal/');
        $this->assertSanitized();
    }

    public function testLongAndFormattedTextIsKept(): void
    {
        $long = '<p><span style="font-size:18px">Styled</span></p>' . str_repeat('<p>Filler text.</p>', 2000)
            . '<p><a href="/nl/dashboard/portfolio/">Last paragraph</a></p>';

        $this->client->request(
            'POST',
            '/api/journals',
            server: ['CONTENT_TYPE' => 'application/ld+json', 'HTTP_ACCEPT' => 'application/ld+json'],
            content: json_encode(['title' => 'Long', 'content' => $long], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(201);
        $id = basename((string) $this->client->getResponse()->headers->get('Location'));

        $this->client->request('GET', '/nl/dashboard/journal/' . $id);

        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('style="font-size:18px"', $content);
        self::assertStringContainsString('href="/nl/dashboard/portfolio/">Last paragraph', $content);
    }

    public function testTickerDescriptionIsSanitized(): void
    {
        $ticker = TickerFactory::createOne(['description' => self::MALICIOUS]);

        $this->client->request('GET', '/nl/dashboard/admin/ticker/' . $ticker->getId());

        $this->assertSanitized();
    }

    private function assertSanitized(): void
    {
        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('<strong>Kept</strong>', $content);
        self::assertStringNotContainsString('alert("x")', $content);
        self::assertStringNotContainsString('onerror', $content);
        self::assertStringNotContainsString('position:fixed', $content);
        self::assertStringNotContainsString('class="fixed inset-0"', $content);
    }

    public function testATickerWithoutDescriptionRenders(): void
    {
        $ticker = TickerFactory::createOne(['description' => null]);

        $this->client->request('GET', '/nl/dashboard/admin/ticker/' . $ticker->getId());

        self::assertResponseIsSuccessful();
    }
}
