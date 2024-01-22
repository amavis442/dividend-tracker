<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240122221009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE transaction set fx_fee = 0  WHERE fx_fee IS NULL');
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction ALTER fx_fee TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE transaction ALTER fx_fee SET DEFAULT \'0\'');
        $this->addSql('ALTER TABLE transaction ALTER fx_fee SET NOT NULL');
        $this->addSql('UPDATE transaction set fx_fee = fx_fee / 1000  WHERE fx_fee > 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE transaction set fx_fee = fx_fee * 1000  WHERE fx_fee > 0');
        $this->addSql('ALTER TABLE transaction ALTER fx_fee TYPE INT');
        $this->addSql('ALTER TABLE transaction ALTER fx_fee DROP DEFAULT');
        $this->addSql('ALTER TABLE transaction ALTER fx_fee DROP NOT NULL');
    }
}
