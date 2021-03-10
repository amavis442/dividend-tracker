<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190919183802 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE research_files (research_id INT NOT NULL, files_id INT NOT NULL, INDEX IDX_80B322BD7909E1ED (research_id), UNIQUE INDEX UNIQ_80B322BDA3E65B2F (files_id), PRIMARY KEY(research_id, files_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE files (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE research_files ADD CONSTRAINT FK_80B322BD7909E1ED FOREIGN KEY (research_id) REFERENCES research (id)');
        $this->addSql('ALTER TABLE research_files ADD CONSTRAINT FK_80B322BDA3E65B2F FOREIGN KEY (files_id) REFERENCES files (id)');
        $this->addSql('ALTER TABLE research CHANGE title title VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE research_files DROP FOREIGN KEY FK_80B322BDA3E65B2F');
        $this->addSql('DROP TABLE research_files');
        $this->addSql('DROP TABLE files');
    }
}
