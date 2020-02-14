<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200214134931 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE research_files');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE research_files (research_id INT NOT NULL, files_id INT NOT NULL, UNIQUE INDEX UNIQ_80B322BDA3E65B2F (files_id), INDEX IDX_80B322BD7909E1ED (research_id), PRIMARY KEY(research_id, files_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE research_files ADD CONSTRAINT FK_80B322BD7909E1ED FOREIGN KEY (research_id) REFERENCES research (id)');
        $this->addSql('ALTER TABLE research_files ADD CONSTRAINT FK_80B322BDA3E65B2F FOREIGN KEY (files_id) REFERENCES files (id)');
    }
}
