<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240122225406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE position set price = 0  WHERE price IS NULL');
        $this->addSql('UPDATE position set amount = 0  WHERE amount IS NULL');
        $this->addSql('UPDATE position set profit = 0  WHERE profit IS NULL');
        $this->addSql('UPDATE position set allocation = 0  WHERE allocation IS NULL');


        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE position ALTER price TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE position ALTER price SET DEFAULT \'0\'');
        $this->addSql('ALTER TABLE position ALTER price SET NOT NULL');
        $this->addSql('ALTER TABLE position ALTER amount TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE position ALTER amount SET DEFAULT \'0\'');
        $this->addSql('ALTER TABLE position ALTER profit TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE position ALTER profit SET DEFAULT \'0\'');
        $this->addSql('ALTER TABLE position ALTER profit SET NOT NULL');
        $this->addSql('ALTER TABLE position ALTER allocation TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE position ALTER allocation SET DEFAULT \'0\'');
        $this->addSql('ALTER TABLE position ALTER allocation SET NOT NULL');

        $this->addSql('UPDATE position set price = price / 1000  WHERE price > 0');
        $this->addSql('UPDATE position set amount = amount / 10000000  WHERE amount > 0');
        $this->addSql('UPDATE position set profit = profit / 1000  WHERE profit > 0');
        $this->addSql('UPDATE position set allocation = allocation / 1000  WHERE allocation > 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE position set price = price * 1000  WHERE price > 0');
        $this->addSql('UPDATE position set amount = amount * 10000000  WHERE amount > 0');
        $this->addSql('UPDATE position set profit = profit * 1000  WHERE profit > 0');
        $this->addSql('UPDATE position set allocation = allocation * 1000  WHERE allocation > 0');

        $this->addSql('ALTER TABLE position ALTER price TYPE INT');
        $this->addSql('ALTER TABLE position ALTER price DROP DEFAULT');
        $this->addSql('ALTER TABLE position ALTER price DROP NOT NULL');
        $this->addSql('ALTER TABLE position ALTER amount TYPE BIGINT');
        $this->addSql('ALTER TABLE position ALTER amount DROP DEFAULT');
        $this->addSql('ALTER TABLE position ALTER profit TYPE INT');
        $this->addSql('ALTER TABLE position ALTER profit DROP DEFAULT');
        $this->addSql('ALTER TABLE position ALTER profit DROP NOT NULL');
        $this->addSql('ALTER TABLE position ALTER allocation TYPE INT');
        $this->addSql('ALTER TABLE position ALTER allocation DROP DEFAULT');
        $this->addSql('ALTER TABLE position ALTER allocation DROP NOT NULL');
    }
}
