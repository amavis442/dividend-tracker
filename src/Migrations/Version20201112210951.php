<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201112210951 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payment ADD amount BIGINT DEFAULT NULL');
        $this->addSql('UPDATE payment SET amount = units * 100000');
        $this->addSql('ALTER TABLE payment DROP units');
        
        $this->addSql('ALTER TABLE position CHANGE amount amount BIGINT NOT NULL');
        $this->addSql('UPDATE position SET amount = amount * 100000');

        $this->addSql('ALTER TABLE transaction CHANGE amount amount BIGINT NOT NULL');
        $this->addSql('UPDATE transaction SET amount = amount * 100000');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payment ADD units INT DEFAULT NULL');
        $this->addSql('UPDATE payment SET units = amount / 100000');
        $this->addSql('ALTER TABLE payment DROP amount');
        
        $this->addSql('UPDATE position SET amount = amount / 100000');
        $this->addSql('ALTER TABLE position CHANGE amount amount INT NOT NULL');

        $this->addSql('UPDATE transaction SET amount = amount / 100000');
        $this->addSql('ALTER TABLE transaction CHANGE amount amount INT NOT NULL');
    }
}
