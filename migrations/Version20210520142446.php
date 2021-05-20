<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210520142446 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticker ADD tax_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ticker ADD CONSTRAINT FK_7EC30896B2A824D8 FOREIGN KEY (tax_id) REFERENCES tax (id)');
        $this->addSql('CREATE INDEX IDX_7EC30896B2A824D8 ON ticker (tax_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticker DROP FOREIGN KEY FK_7EC30896B2A824D8');
        $this->addSql('DROP INDEX IDX_7EC30896B2A824D8 ON ticker');
        $this->addSql('ALTER TABLE ticker DROP tax_id');
    }
}
