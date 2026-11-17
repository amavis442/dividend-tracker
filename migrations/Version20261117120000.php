<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261117120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Position: add status (open/closed/pending) and make ticker_id nullable for Trading212 instruments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "position" ADD status VARCHAR(16) DEFAULT \'open\' NOT NULL');
        $this->addSql('UPDATE "position" SET status = \'closed\' WHERE closed = true');
        $this->addSql('ALTER TABLE "position" ALTER ticker_id DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Fails when positions without a ticker exist; remove or link those first.
        $this->addSql('ALTER TABLE "position" ALTER ticker_id SET NOT NULL');
        $this->addSql('ALTER TABLE "position" DROP status');
    }
}
