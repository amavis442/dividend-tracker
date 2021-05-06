<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210506162813 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction ADD pie_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1BB3AA24D FOREIGN KEY (pie_id) REFERENCES pie (id)');
        $this->addSql('CREATE INDEX IDX_723705D1BB3AA24D ON transaction (pie_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1BB3AA24D');
        $this->addSql('DROP INDEX IDX_723705D1BB3AA24D ON transaction');
        $this->addSql('ALTER TABLE transaction DROP pie_id');
    }
}
