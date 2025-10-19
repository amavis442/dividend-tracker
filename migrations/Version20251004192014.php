<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251004192014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM corporate_action');
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE corporate_action DROP CONSTRAINT fk_8f0b005edd842e46');
        $this->addSql('DROP INDEX idx_8f0b005edd842e46');
        $this->addSql('ALTER TABLE corporate_action RENAME COLUMN position_id TO ticker_id');
        $this->addSql('ALTER TABLE corporate_action ADD CONSTRAINT FK_8F0B005E556B180E FOREIGN KEY (ticker_id) REFERENCES ticker (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_8F0B005E556B180E ON corporate_action (ticker_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE corporate_action DROP CONSTRAINT FK_8F0B005E556B180E');
        $this->addSql('DROP INDEX IDX_8F0B005E556B180E');
        $this->addSql('ALTER TABLE corporate_action RENAME COLUMN ticker_id TO position_id');
        $this->addSql('ALTER TABLE corporate_action ADD CONSTRAINT fk_8f0b005edd842e46 FOREIGN KEY (position_id) REFERENCES "position" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_8f0b005edd842e46 ON corporate_action (position_id)');
    }
}
