<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240122231706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {

        $this->addSql('UPDATE calendar set cash_amount = 0  WHERE cash_amount IS NULL');

        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE calendar ALTER cash_amount TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE calendar ALTER cash_amount SET DEFAULT \'0\'');

        $this->addSql('UPDATE calendar set cash_amount = cash_amount / 1000  WHERE cash_amount > 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE calendar set cash_amount = cash_amount * 1000  WHERE cash_amount > 0');

        $this->addSql('ALTER TABLE calendar ALTER cash_amount TYPE INT');
        $this->addSql('ALTER TABLE calendar ALTER cash_amount DROP DEFAULT');
    }
}
