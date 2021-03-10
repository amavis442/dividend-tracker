<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201111160343 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payment ADD position_id INT DEFAULT NULL, ADD units INT DEFAULT NULL');
        $this->addSql('UPDATE payment SET units = stocks');
        $this->addSql('ALTER TABLE payment DROP stocks');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DDD842E46 FOREIGN KEY (position_id) REFERENCES position (id)');
        $this->addSql('CREATE INDEX IDX_6D28840DDD842E46 ON payment (position_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DDD842E46');
        $this->addSql('DROP INDEX IDX_6D28840DDD842E46 ON payment');
        $this->addSql('ALTER TABLE payment ADD stocks INT DEFAULT NULL, DROP position_id');
        $this->addSql('UPDATE payment SET stocks = units');
        $this->addSql('ALTER TABLE payment DROP units');

    }
}
