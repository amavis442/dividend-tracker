<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250510185509 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE trading212_pie_instrument ADD ticker_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trading212_pie_instrument ADD CONSTRAINT FK_F0186C94556B180E FOREIGN KEY (ticker_id) REFERENCES ticker (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F0186C94556B180E ON trading212_pie_instrument (ticker_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE trading212_pie_instrument DROP CONSTRAINT FK_F0186C94556B180E
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_F0186C94556B180E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trading212_pie_instrument DROP ticker_id
        SQL);
    }
}
