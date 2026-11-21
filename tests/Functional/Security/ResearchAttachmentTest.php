<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\User;
use App\Factory\TickerFactory;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Research attachments are private files with a type and size whitelist (#75).
 */
#[Group('security')]
final class ResearchAttachmentTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const PDF = "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n";

    private KernelBrowser $client;
    private Connection $connection;
    private string $documentsDirectory;
    private string $tmpDirectory;
    private int $tickerId;
    private int $adminId;
    private int $userId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->connection = static::getContainer()->get(Connection::class);
        $this->documentsDirectory = (string) static::getContainer()->getParameter('documents_directory');
        $this->tmpDirectory = sys_get_temp_dir() . '/research-attachment-test-' . bin2hex(random_bytes(4));
        mkdir($this->tmpDirectory);

        $this->tickerId = TickerFactory::createOne(['symbol' => 'DOCS'])->getId();
        $admin = UserFactory::createOne(['email' => 'admin@example.test', 'roles' => ['ROLE_USER', 'ROLE_ADMIN']]);
        $this->adminId = $admin->getId();
        $this->userId = UserFactory::createOne(['email' => 'reader@example.test', 'roles' => ['ROLE_USER']])->getId();
    }

    protected function tearDown(): void
    {
        foreach ($this->storedNames() as $name) {
            @unlink($this->documentsDirectory . '/' . $name);
        }
        array_map('unlink', glob($this->tmpDirectory . '/*') ?: []);
        rmdir($this->tmpDirectory);
        parent::tearDown();
    }

    public function testAnAllowedFileIsStoredOutsideThePublicDirectory(): void
    {
        $this->upload('report.pdf', self::PDF);

        self::assertResponseRedirects();
        $names = $this->storedNames();
        self::assertCount(1, $names);
        self::assertStringEndsWith('.pdf', $names[0]);
        self::assertFileExists($this->documentsDirectory . '/' . $names[0]);
        self::assertStringNotContainsString('/public/', $this->documentsDirectory);
    }

    public function testDisallowedTypesAreRejected(): void
    {
        $files = [
            'page.html' => '<html><body><script>alert(1)</script></body></html>',
            'image.svg' => '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
            'script.php' => '<?php echo 1;',
            // A PDF extension with other content is rejected too.
            'fake.pdf' => '<html><body>not a pdf</body></html>',
        ];
        foreach ($files as $name => $content) {
            $this->upload($name, $content);

            self::assertResponseStatusCodeSame(422, $name);
        }
        self::assertSame([], $this->storedNames());
    }

    public function testTooLargeFilesAreRejected(): void
    {
        $this->upload('big.pdf', self::PDF . str_repeat('0', 10 * 1024 * 1024));

        self::assertResponseStatusCodeSame(422);
        self::assertSame([], $this->storedNames());
    }

    public function testALoggedInUserCanDownloadAndAVisitorCannot(): void
    {
        $this->upload('report.pdf', self::PDF);
        $attachmentId = (int) $this->connection->fetchOne('select id from attachment');

        $this->client->restart();
        $this->client->request('GET', $this->downloadPath($attachmentId));
        self::assertResponseRedirects();
        self::assertStringContainsString('/login', (string) $this->client->getResponse()->headers->get('Location'));

        $this->login($this->userId);
        $this->client->request('GET', $this->downloadPath($attachmentId));
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/pdf');
        self::assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
        $disposition = (string) $this->client->getResponse()->headers->get('Content-Disposition');
        self::assertStringStartsWith('inline;', $disposition);
    }

    public function testOnlyAnAdminCanUpload(): void
    {
        $this->login($this->userId);
        $this->client->request('GET', '/nl/dashboard/research/create/' . $this->tickerId);

        self::assertResponseStatusCodeSame(403);
    }

    private function upload(string $name, string $content): void
    {
        $this->login($this->adminId);
        $crawler = $this->client->request('GET', '/nl/dashboard/research/create/' . $this->tickerId);
        self::assertResponseIsSuccessful();

        $path = $this->tmpDirectory . '/' . $name;
        file_put_contents($path, $content);
        $form = $crawler->filter('form[name="research"]')->form(['research[title]' => 'Annual report']);

        $this->client->request(
            'POST',
            $form->getUri(),
            $form->getPhpValues(),
            ['research' => ['attachments' => [new UploadedFile($path, $name, null, null, true)]]]
        );
    }

    /**
     * @return list<string>
     */
    private function storedNames(): array
    {
        return $this->connection->fetchFirstColumn('select attachment_name from attachment order by id');
    }

    private function downloadPath(int $attachmentId): string
    {
        return '/nl/dashboard/research/attachment/' . $attachmentId;
    }

    private function login(int $userId): void
    {
        $user = static::getContainer()->get(UserRepository::class)->find($userId);
        self::assertInstanceOf(User::class, $user);
        $this->client->loginUser($user);
    }
}
