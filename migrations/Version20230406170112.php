<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230406170112 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE attachment CHANGE attachment_size attachment_size BIGINT DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE journal_taxonomy DROP FOREIGN KEY FK_2DA215849557E6F6');
        $this->addSql('ALTER TABLE journal_taxonomy DROP FOREIGN KEY FK_2DA21584478E8802');
        $this->addSql('ALTER TABLE journal_taxonomy ADD CONSTRAINT FK_2DA215849557E6F6 FOREIGN KEY (taxonomy_id) REFERENCES taxonomy (id)');
        $this->addSql('ALTER TABLE journal_taxonomy ADD CONSTRAINT FK_2DA21584478E8802 FOREIGN KEY (journal_id) REFERENCES journal (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE journal_taxonomy DROP FOREIGN KEY FK_2DA21584478E8802');
        $this->addSql('ALTER TABLE journal_taxonomy DROP FOREIGN KEY FK_2DA215849557E6F6');
        $this->addSql('ALTER TABLE journal_taxonomy ADD CONSTRAINT FK_2DA21584478E8802 FOREIGN KEY (journal_id) REFERENCES journal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE journal_taxonomy ADD CONSTRAINT FK_2DA215849557E6F6 FOREIGN KEY (taxonomy_id) REFERENCES taxonomy (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE attachment CHANGE attachment_size attachment_size INT NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
    }
}
