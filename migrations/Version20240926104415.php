<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240926104415 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE portfolio ADD num_active_position INT NOT NULL');
        $this->addSql('ALTER TABLE portfolio ADD profit DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE portfolio ADD total_dividend DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE portfolio ADD allocated DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE portfolio ADD goalpercentage DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE transaction ALTER profit TYPE DOUBLE PRECISION');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction ALTER profit TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE portfolio DROP num_active_position');
        $this->addSql('ALTER TABLE portfolio DROP profit');
        $this->addSql('ALTER TABLE portfolio DROP total_dividend');
        $this->addSql('ALTER TABLE portfolio DROP allocated');
        $this->addSql('ALTER TABLE portfolio DROP goalpercentage');
    }
}
