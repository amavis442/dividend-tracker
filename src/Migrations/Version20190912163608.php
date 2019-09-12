<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190912163608 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE research (id INT AUTO_INCREMENT NOT NULL, ticker_id INT NOT NULL, title VARCHAR(255) DEFAULT NULL, info LONGTEXT DEFAULT NULL, INDEX IDX_57EB50C2556B180E (ticker_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE file (id INT AUTO_INCREMENT NOT NULL, research_id INT NOT NULL, title VARCHAR(255) NOT NULL, path VARCHAR(255) NOT NULL, filename VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_8C9F36107909E1ED (research_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE research ADD CONSTRAINT FK_57EB50C2556B180E FOREIGN KEY (ticker_id) REFERENCES ticker (id)');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F36107909E1ED FOREIGN KEY (research_id) REFERENCES research (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE file DROP FOREIGN KEY FK_8C9F36107909E1ED');
        $this->addSql('DROP TABLE research');
        $this->addSql('DROP TABLE file');
    }
}
