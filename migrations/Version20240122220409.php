<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240122220409 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE transaction set original_price = 0  WHERE original_price IS NULL');
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction ALTER original_price TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE transaction ALTER original_price SET DEFAULT \'0\'');
        $this->addSql('ALTER TABLE transaction ALTER original_price SET NOT NULL');
        $this->addSql('UPDATE transaction set original_price = original_price / 1000  WHERE original_price > 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE transaction set original_price = original_price * 1000  WHERE original_price > 0');

        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction ALTER original_price TYPE INT');
        $this->addSql('ALTER TABLE transaction ALTER original_price DROP DEFAULT');
        $this->addSql('ALTER TABLE transaction ALTER original_price DROP NOT NULL');
    }
}
