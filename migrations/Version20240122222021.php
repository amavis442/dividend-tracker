<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240122222021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE transaction set price = 0  WHERE price IS NULL');
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction ALTER price TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE transaction ALTER price SET DEFAULT \'0\'');
        $this->addSql('ALTER TABLE transaction ALTER price SET NOT NULL');
        $this->addSql('UPDATE transaction set price = price / 1000 WHERE price > 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE transaction set price = price * 1000  WHERE price > 0');
        $this->addSql('ALTER TABLE transaction ALTER price TYPE INT');
        $this->addSql('ALTER TABLE transaction ALTER price DROP DEFAULT');
        $this->addSql('ALTER TABLE transaction ALTER price DROP NOT NULL');
    }
}
