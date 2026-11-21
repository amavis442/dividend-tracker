<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\ApiKey;
use App\Entity\ApiKeyName;
use App\Entity\User;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use App\Security\SecretBox;
use App\Service\UserManager;
use Doctrine\DBAL\Connection;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Login throttling, password and email rules, and encrypted API keys (#77).
 */
#[Group('security')]
final class AccountHardeningTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const EMAIL = 'hardening@example.test';
    private const PASSWORD = 'Correct-Horse-Battery-9';
    private const WEAK_PASSWORDS = ['x', 'password', 'abcdefghijkl'];
    private const PLAIN_API_KEY = 'T212-plain-api-key-0123456789';

    private KernelBrowser $client;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->connection = static::getContainer()->get(Connection::class);
        // The limiter keeps its state on disk; start every test without failed attempts.
        static::getContainer()->get('cache.rate_limiter')->clear();

        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD, 'roles' => ['ROLE_USER']]);
    }

    public function testRepeatedFailedFormLoginsAreLimited(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->formLogin('wrong-password-' . $attempt);
            self::assertStringContainsString('/login', $this->location(), 'attempt ' . $attempt);
        }

        // The sixth attempt is refused, even with the right password.
        $this->formLogin(self::PASSWORD);
        self::assertStringContainsString('/login', $this->location());
        $this->client->followRedirect();
        self::assertSelectorExists('form input[name="_username"]');
        self::assertAnySelectorTextContains('div', 'inlogpogingen');
    }

    public function testACorrectLoginStillWorksBeforeTheLimit(): void
    {
        $this->formLogin('wrong-password');
        $this->formLogin(self::PASSWORD);

        self::assertStringNotContainsString('/login', $this->location());
    }

    public function testRepeatedFailedJsonLoginsAreLimited(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->jsonLogin('wrong-password-' . $attempt);
            self::assertResponseStatusCodeSame(401, 'attempt ' . $attempt);
        }

        $this->jsonLogin(self::PASSWORD);
        self::assertResponseStatusCodeSame(401);
        self::assertStringContainsString('inlogpogingen', (string) $this->client->getResponse()->getContent());
    }

    public function testWeakPasswordsAndInvalidEmailsAreRejectedByTheApi(): void
    {
        $this->loginAdmin();

        foreach (self::WEAK_PASSWORDS as $password) {
            $this->postUser('new@example.test', $password);
            self::assertResponseStatusCodeSame(422, $password);
        }

        $this->postUser('not-an-email', self::PASSWORD);
        self::assertResponseStatusCodeSame(422);

        $this->postUser('new@example.test', self::PASSWORD);
        self::assertResponseStatusCodeSame(201);
    }

    public function testWeakPasswordsAreRejectedOnRegistrationAndTheAccountPage(): void
    {
        $this->loginAdmin();

        $crawler = $this->client->request('GET', '/nl/dashboard/register');
        $form = $crawler->selectButton('Register')->form();
        $form['registration_form[email]'] = 'invited@example.test';
        $form['registration_form[plainPassword]'] = 'password';
        $form['registration_form[agreeTerms]']->tick();
        $this->client->submit($form);
        self::assertSelectorExists('#registration_form_plainPassword_error1');
        $count = $this->connection->fetchOne("select count(*) from users where email = 'invited@example.test'");
        self::assertSame(0, (int) $count);

        $hashBefore = $this->connection->fetchOne('select password from users where email = ?', [self::EMAIL]);
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => self::EMAIL]);
        self::assertInstanceOf(User::class, $user);
        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/nl/dashboard/account/update');
        $form = $crawler->filter('form[name="account_form"]')->form();
        $form['account_form[plainPassword]'] = 'x';
        $this->client->submit($form);
        self::assertSelectorExists('#account_form_plainPassword_error1');
        $hashAfter = $this->connection->fetchOne('select password from users where email = ?', [self::EMAIL]);
        self::assertSame($hashBefore, $hashAfter);
    }

    public function testTheUserManagerRejectsWeakPasswords(): void
    {
        $manager = static::getContainer()->get(UserManager::class);

        $this->expectException(InvalidArgumentException::class);
        $manager->create('cli@example.test', 'short', ['ROLE_USER']);
    }

    public function testApiKeysAreStoredEncrypted(): void
    {
        $id = $this->storeApiKey(self::PLAIN_API_KEY);

        $stored = (string) $this->connection->fetchOne('select api_key from api_key where id = ?', [$id]);
        self::assertStringStartsWith(SecretBox::PREFIX, $stored);
        self::assertStringNotContainsString(self::PLAIN_API_KEY, $stored);

        $manager = static::getContainer()->get('doctrine')->getManager();
        $manager->clear();
        self::assertSame(self::PLAIN_API_KEY, $manager->find(ApiKey::class, $id)?->getApiKey());
    }

    public function testTheCommandEncryptsExistingPlainTextKeys(): void
    {
        $id = $this->storeApiKey(self::PLAIN_API_KEY);
        // A key stored before encryption existed.
        $this->connection->update('api_key', ['api_key' => 'legacy-plain-key'], ['id' => $id]);

        $command = new CommandTester((new Application(static::$kernel))->find('app:api-keys:encrypt'));
        self::assertSame(0, $command->execute([]));
        self::assertStringContainsString('Encrypted 1 of 1', $command->getDisplay());

        $stored = (string) $this->connection->fetchOne('select api_key from api_key where id = ?', [$id]);
        self::assertStringStartsWith(SecretBox::PREFIX, $stored);
        self::assertSame('legacy-plain-key', static::getContainer()->get(SecretBox::class)->decrypt($stored));

        $command->execute([]);
        self::assertStringContainsString('Encrypted 0 of 1', $command->getDisplay());
    }

    private function storeApiKey(string $plainKey): int
    {
        $manager = static::getContainer()->get('doctrine')->getManager();
        $name = (new ApiKeyName())->setKeyName('Trading212');
        $manager->persist($name);
        $apiKey = (new ApiKey())
            ->setApiKey($plainKey)
            ->setApiKeyName($name)
            ->setUser($manager->getRepository(User::class)->findOneBy(['email' => self::EMAIL]));
        $manager->persist($apiKey);
        $manager->flush();

        return (int) $apiKey->getId();
    }

    private function formLogin(string $password): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form([
            '_username' => self::EMAIL,
            '_password' => $password,
        ]);
        $this->client->submit($form);
    }

    private function jsonLogin(string $password): void
    {
        $this->client->request(
            'POST',
            '/login/json_result',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => self::EMAIL, 'password' => $password], JSON_THROW_ON_ERROR)
        );
    }

    private function postUser(string $email, string $password): void
    {
        $this->client->request(
            'POST',
            '/api/users',
            server: ['CONTENT_TYPE' => 'application/ld+json', 'HTTP_ACCEPT' => 'application/ld+json'],
            content: json_encode(['email' => $email, 'password' => $password], JSON_THROW_ON_ERROR)
        );
    }

    private function loginAdmin(): void
    {
        $id = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']])->getId();
        $admin = static::getContainer()->get(UserRepository::class)->find($id);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin);
    }

    private function location(): string
    {
        return (string) $this->client->getResponse()->headers->get('Location');
    }
}
