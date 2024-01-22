<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240122215705 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE transaction set allocation = 0  WHERE allocation IS NULL');

        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction ALTER allocation TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE transaction ALTER allocation SET DEFAULT \'0\'');
        $this->addSql('ALTER TABLE transaction ALTER allocation SET NOT NULL');

        $this->addSql('UPDATE transaction set allocation = allocation / 1000  WHERE allocation > 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE transaction set allocation = allocation * 1000  WHERE allocation > 0');

        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction ALTER allocation TYPE INT');
        $this->addSql('ALTER TABLE transaction ALTER allocation DROP DEFAULT');
        $this->addSql('ALTER TABLE transaction ALTER allocation DROP NOT NULL');
    }
}
