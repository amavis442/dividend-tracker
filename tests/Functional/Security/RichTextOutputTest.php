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

    private const MALICIOUS = '<p><strong>Kept</strong></p><script>alert("x")</script><img src="x" onerror="alert(1)">';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $email = UserFactory::createOne()->getEmail();
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
    }
}
