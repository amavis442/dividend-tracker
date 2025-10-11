<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251011070005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE calendar SET dividend_type=\'Regular\' WHERE dividend_type is null;');
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE calendar ALTER dividend_type SET DEFAULT \'Regular\'');
        $this->addSql('ALTER TABLE calendar ALTER dividend_type SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE calendar ALTER dividend_type DROP DEFAULT');
        $this->addSql('ALTER TABLE calendar ALTER dividend_type DROP NOT NULL');
    }
}
