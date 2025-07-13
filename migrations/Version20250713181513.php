<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250713181513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE import_files ADD owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE import_files ADD CONSTRAINT FK_EB60EF977E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_EB60EF977E3C61F9 ON import_files (owner_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE import_files DROP CONSTRAINT FK_EB60EF977E3C61F9');
        $this->addSql('DROP INDEX IDX_EB60EF977E3C61F9');
        $this->addSql('ALTER TABLE import_files DROP owner_id');
    }
}
