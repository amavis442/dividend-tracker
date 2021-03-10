<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190903172148 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payment ADD calendar_id INT DEFAULT NULL, DROP ex_dividend_date, DROP record_date');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DA40A2C8 FOREIGN KEY (calendar_id) REFERENCES calendar (id)');
        $this->addSql('CREATE INDEX IDX_6D28840DA40A2C8 ON payment (calendar_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DA40A2C8');
        $this->addSql('DROP INDEX IDX_6D28840DA40A2C8 ON payment');
        $this->addSql('ALTER TABLE payment ADD ex_dividend_date DATETIME NOT NULL, ADD record_date DATETIME DEFAULT \'NULL\', DROP calendar_id');
    }
}
