<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250509165540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE trading212_pie_instrument ADD trading212_pie_meta_data_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trading212_pie_instrument ADD CONSTRAINT FK_F0186C942F5F72BA FOREIGN KEY (trading212_pie_meta_data_id) REFERENCES trading212_pie_meta_data (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F0186C942F5F72BA ON trading212_pie_instrument (trading212_pie_meta_data_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trading212_pie_meta_data ADD pie_name VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE trading212_pie_instrument DROP CONSTRAINT FK_F0186C942F5F72BA
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_F0186C942F5F72BA
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trading212_pie_instrument DROP trading212_pie_meta_data_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trading212_pie_meta_data DROP pie_name
        SQL);
    }
}
