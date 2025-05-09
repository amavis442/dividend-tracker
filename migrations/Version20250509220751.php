<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250509220751 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE trading212_pie_meta_data ADD pie_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trading212_pie_meta_data ADD CONSTRAINT FK_95275C94BB3AA24D FOREIGN KEY (pie_id) REFERENCES pie (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_95275C94BB3AA24D ON trading212_pie_meta_data (pie_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE trading212_pie_meta_data DROP CONSTRAINT FK_95275C94BB3AA24D
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_95275C94BB3AA24D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trading212_pie_meta_data DROP pie_id
        SQL);
    }
}
