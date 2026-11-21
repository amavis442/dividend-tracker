<?php

declare(strict_types=1);

namespace App\Command;

use App\Security\SecretBox;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Encrypts API keys that were stored before encryption existed. Run it once
 * after deploying #77, with APP_ENCRYPTION_KEY set; running it again does nothing.
 */
#[AsCommand(
    name: 'app:api-keys:encrypt',
    description: 'Encrypt API keys that are still stored in plain text',
)]
final class EncryptApiKeysCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly SecretBox $secretBox,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $rows = $this->connection->fetchAllAssociative('SELECT id, api_key FROM api_key');
        $encrypted = 0;
        foreach ($rows as $row) {
            if (SecretBox::isEncrypted((string) $row['api_key'])) {
                continue;
            }
            $this->connection->update(
                'api_key',
                ['api_key' => $this->secretBox->encrypt((string) $row['api_key'])],
                ['id' => $row['id']]
            );
            $encrypted++;
        }

        $io->success(sprintf('Encrypted %d of %d API key(s).', $encrypted, count($rows)));

        return Command::SUCCESS;
    }
}
