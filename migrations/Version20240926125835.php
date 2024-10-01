<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240926125835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE position ADD portfolio_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE position ADD CONSTRAINT FK_462CE4F5B96B5643 FOREIGN KEY (portfolio_id) REFERENCES portfolio (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_462CE4F5B96B5643 ON position (portfolio_id)');
        $this->addSql('ALTER TABLE transaction ALTER profit TYPE DOUBLE PRECISION');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction ALTER profit TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE position DROP CONSTRAINT FK_462CE4F5B96B5643');
        $this->addSql('DROP INDEX IDX_462CE4F5B96B5643');
        $this->addSql('ALTER TABLE position DROP portfolio_id');
    }
}
