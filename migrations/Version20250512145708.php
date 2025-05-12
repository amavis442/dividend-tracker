<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250512145708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE trading212_pie_meta_data ADD gained DOUBLE PRECISION DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trading212_pie_meta_data ADD reinvested DOUBLE PRECISION DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trading212_pie_meta_data ADD in_cash DOUBLE PRECISION DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE trading212_pie_meta_data DROP gained
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trading212_pie_meta_data DROP reinvested
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trading212_pie_meta_data DROP in_cash
        SQL);
    }
}
