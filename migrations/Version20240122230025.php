<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240122230025 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {

        $this->addSql('UPDATE payment set dividend = 0  WHERE dividend IS NULL');
        $this->addSql('UPDATE payment set amount = 0  WHERE amount IS NULL');
        $this->addSql('UPDATE payment set tax_withold = 0  WHERE tax_withold IS NULL');
        $this->addSql('UPDATE payment set dividend_paid = 0  WHERE dividend_paid IS NULL');


        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment ALTER dividend TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE payment ALTER dividend SET DEFAULT \'0\'');
        $this->addSql('ALTER TABLE payment ALTER dividend SET NOT NULL');
        $this->addSql('ALTER TABLE payment ALTER amount TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE payment ALTER amount SET DEFAULT \'0\'');
        $this->addSql('ALTER TABLE payment ALTER amount SET NOT NULL');
        $this->addSql('ALTER TABLE payment ALTER tax_withold TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE payment ALTER tax_withold SET DEFAULT \'0\'');
        $this->addSql('ALTER TABLE payment ALTER tax_withold SET NOT NULL');
        $this->addSql('ALTER TABLE payment ALTER dividend_paid TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE payment ALTER dividend_paid SET DEFAULT \'0\'');
        $this->addSql('ALTER TABLE payment ALTER dividend_paid SET NOT NULL');

        $this->addSql('UPDATE payment set dividend = dividend / 1000  WHERE dividend > 0');
        $this->addSql('UPDATE payment set amount = amount / 10000000  WHERE amount > 0');
        $this->addSql('UPDATE payment set tax_withold = tax_withold / 1000  WHERE tax_withold > 0');
        $this->addSql('UPDATE payment set dividend_paid = dividend_paid / 1000  WHERE dividend_paid > 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE payment set dividend = dividend * 1000  WHERE dividend > 0');
        $this->addSql('UPDATE payment set amount = amount * 10000000  WHERE amount > 0');
        $this->addSql('UPDATE payment set tax_withold = tax_withold * 1000  WHERE tax_withold > 0');
        $this->addSql('UPDATE payment set dividend_paid = dividend_paid * 1000  WHERE dividend_paid > 0');

        $this->addSql('ALTER TABLE payment ALTER dividend TYPE INT');
        $this->addSql('ALTER TABLE payment ALTER dividend DROP DEFAULT');
        $this->addSql('ALTER TABLE payment ALTER amount TYPE BIGINT');
        $this->addSql('ALTER TABLE payment ALTER amount DROP DEFAULT');
        $this->addSql('ALTER TABLE payment ALTER amount DROP NOT NULL');
        $this->addSql('ALTER TABLE payment ALTER tax_withold TYPE INT');
        $this->addSql('ALTER TABLE payment ALTER tax_withold DROP DEFAULT');
        $this->addSql('ALTER TABLE payment ALTER tax_withold DROP NOT NULL');
        $this->addSql('ALTER TABLE payment ALTER dividend_paid TYPE INT');
        $this->addSql('ALTER TABLE payment ALTER dividend_paid DROP DEFAULT');
        $this->addSql('ALTER TABLE payment ALTER dividend_paid DROP NOT NULL');
    }
}
