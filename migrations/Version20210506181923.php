<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210506181923 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pie ADD user_id INT NOT NULL');
        $this->addSql('UPDATE pie set user_id = 2');
        $this->addSql('ALTER TABLE pie ADD CONSTRAINT FK_2257F47BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_2257F47BA76ED395 ON pie (user_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pie DROP FOREIGN KEY FK_2257F47BA76ED395');
        $this->addSql('DROP INDEX IDX_2257F47BA76ED395 ON pie');
        $this->addSql('ALTER TABLE pie DROP user_id');
    }
}
