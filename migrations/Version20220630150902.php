<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220630150902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE journal_taxonomy (journal_id INT NOT NULL, taxonomy_id INT NOT NULL, INDEX IDX_2DA21584478E8802 (journal_id), INDEX IDX_2DA215849557E6F6 (taxonomy_id), PRIMARY KEY(journal_id, taxonomy_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE taxonomy (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE journal_taxonomy ADD CONSTRAINT FK_2DA21584478E8802 FOREIGN KEY (journal_id) REFERENCES journal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE journal_taxonomy ADD CONSTRAINT FK_2DA215849557E6F6 FOREIGN KEY (taxonomy_id) REFERENCES taxonomy (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE journal_taxonomy DROP FOREIGN KEY FK_2DA215849557E6F6');
        $this->addSql('DROP TABLE journal_taxonomy');
        $this->addSql('DROP TABLE taxonomy');
    }
}
