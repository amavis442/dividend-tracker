<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240122223453 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE transaction set exchange_rate = '0' WHERE exchange_rate is NULL");

        // this up() migration is auto-generated, please modify it to your needsALTER TABLE ticker ADD currency_id INT DEFAULT NULL
        $this->addSql('ALTER TABLE transaction ADD exchange_rate1 DOUBLE PRECISION DEFAULT \'0\' NOT NULL');

        $this->addSql('UPDATE transaction set exchange_rate1 = exchange_rate::float');

        $this->addSql('ALTER TABLE transaction DROP exchange_rate');

        $this->addSql('ALTER TABLE transaction ADD exchange_rate DOUBLE PRECISION DEFAULT \'0\' NOT NULL');

        $this->addSql('UPDATE transaction set exchange_rate = exchange_rate1');

        $this->addSql('ALTER TABLE transaction DROP exchange_rate1');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction ALTER exchange_rate TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE transaction ALTER exchange_rate DROP DEFAULT');
        $this->addSql('ALTER TABLE transaction ALTER exchange_rate DROP NOT NULL');
    }
}
