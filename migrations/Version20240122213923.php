<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240122213923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE transaction set profit = 0  WHERE profit IS NULL');

        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction ALTER profit TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE transaction ALTER profit SET DEFAULT \'0\'');
        $this->addSql('ALTER TABLE transaction ALTER profit SET NOT NULL');

        $this->addSql('UPDATE transaction set profit = profit / 1000  WHERE profit > 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE transaction set profit = profit * 1000  WHERE profit > 0');

        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction ALTER profit TYPE INT');
        $this->addSql('ALTER TABLE transaction ALTER profit DROP DEFAULT');
        $this->addSql('ALTER TABLE transaction ALTER profit DROP NOT NULL');
    }
}
