<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240122221509 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE transaction set amount = 0  WHERE amount IS NULL');
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction ALTER amount TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE transaction ALTER amount SET DEFAULT \'0\'');

        $this->addSql('UPDATE transaction set amount = amount / 10000000  WHERE amount > 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE transaction set amount = amount * 10000000  WHERE amount > 0');
        $this->addSql('ALTER TABLE transaction ALTER amount TYPE BIGINT');
        $this->addSql('ALTER TABLE transaction ALTER amount DROP DEFAULT');
    }
}
