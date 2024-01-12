<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240112162047 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE position SET closed=false WHERE closed IS NULL');
        $this->addSql('UPDATE position SET ignore_for_dividend=false WHERE ignore_for_dividend IS NULL');


        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE position ALTER closed SET DEFAULT false');
        $this->addSql('ALTER TABLE position ALTER ignore_for_dividend SET DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE position ALTER closed DROP DEFAULT');
        $this->addSql('ALTER TABLE position ALTER ignore_for_dividend DROP DEFAULT');
    }
}
