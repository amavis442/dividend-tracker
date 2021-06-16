<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210616130350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE position DROP FOREIGN KEY FK_462CE4F5B2A824D8');
        $this->addSql('DROP INDEX IDX_462CE4F5B2A824D8 ON position');
        $this->addSql('ALTER TABLE position ADD dividend_treshold DOUBLE PRECISION DEFAULT NULL, DROP tax_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE position ADD tax_id INT DEFAULT NULL, DROP dividend_treshold');
        $this->addSql('ALTER TABLE position ADD CONSTRAINT FK_462CE4F5B2A824D8 FOREIGN KEY (tax_id) REFERENCES tax (id)');
        $this->addSql('CREATE INDEX IDX_462CE4F5B2A824D8 ON position (tax_id)');
    }
}
