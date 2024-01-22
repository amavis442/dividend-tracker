<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240122233056 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE dividend_tracker set principle = 0  WHERE principle IS NULL');
        $this->addSql('UPDATE dividend_tracker set dividend = 0  WHERE dividend IS NULL');

        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dividend_tracker ALTER principle TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE dividend_tracker ALTER principle SET DEFAULT \'0\'');
        $this->addSql('ALTER TABLE dividend_tracker ALTER dividend TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE dividend_tracker ALTER dividend SET DEFAULT \'0\'');

        $this->addSql('UPDATE dividend_tracker set principle = principle / 1000  WHERE principle > 0');
        $this->addSql('UPDATE dividend_tracker set dividend = dividend / 1000  WHERE dividend > 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE dividend_tracker set principle = principle * 1000  WHERE principle > 0');
        $this->addSql('UPDATE dividend_tracker set dividend = dividend * 1000  WHERE dividend > 0');

        $this->addSql('ALTER TABLE dividend_tracker ALTER principle TYPE INT');
        $this->addSql('ALTER TABLE dividend_tracker ALTER principle DROP DEFAULT');
        $this->addSql('ALTER TABLE dividend_tracker ALTER dividend TYPE INT');
        $this->addSql('ALTER TABLE dividend_tracker ALTER dividend DROP DEFAULT');
    }
}
