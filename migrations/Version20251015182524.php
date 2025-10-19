<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251015182524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "position" DROP adjusted_amount');
        $this->addSql('ALTER TABLE "position" DROP adjusted_average_price');
        $this->addSql('ALTER TABLE "position" DROP adjusted_metrics_last_updated_at');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE position ADD adjusted_amount DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE position ADD adjusted_average_price DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE position ADD adjusted_metrics_last_updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }
}
