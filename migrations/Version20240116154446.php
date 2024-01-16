<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240116154446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticker ADD currency_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ticker ADD CONSTRAINT FK_7EC3089638248176 FOREIGN KEY (currency_id) REFERENCES currency (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_7EC3089638248176 ON ticker (currency_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE ticker DROP CONSTRAINT FK_7EC3089638248176');
        $this->addSql('DROP INDEX IDX_7EC3089638248176');
        $this->addSql('ALTER TABLE ticker DROP currency_id');
    }
}
