<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200215183349 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE position DROP FOREIGN KEY FK_462CE4F52A4B5E6B');
        $this->addSql('DROP INDEX IDX_462CE4F5F7591B23 ON position');
        $this->addSql('ALTER TABLE position DROP closed_currency_id, DROP buy_date, DROP close_date, DROP close_price, DROP broker');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE position ADD closed_currency_id INT DEFAULT NULL, ADD buy_date DATETIME NOT NULL, ADD close_date DATETIME DEFAULT \'NULL\', ADD close_price INT DEFAULT NULL, ADD broker VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'\'\'Trading212\'\'\' NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE position ADD CONSTRAINT FK_462CE4F52A4B5E6B FOREIGN KEY (closed_currency_id) REFERENCES currency (id)');
        $this->addSql('CREATE INDEX IDX_462CE4F5F7591B23 ON position (closed_currency_id)');
    }
}
