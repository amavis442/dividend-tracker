<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Makes room for encrypted API keys (#77). The keys themselves are encrypted by
 * `php bin/console app:api-keys:encrypt`, which needs APP_ENCRYPTION_KEY.
 */
final class Version20261121165212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Longer api_key column for encrypted values (#77)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_key ALTER api_key TYPE VARCHAR(512)');
    }

    public function down(Schema $schema): void
    {
        // Only works while the keys are still short (not encrypted).
        $this->addSql('ALTER TABLE api_key ALTER api_key TYPE VARCHAR(255)');
    }
}
