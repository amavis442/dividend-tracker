<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191115135801 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE position ADD allocation_currency_id INT DEFAULT NULL, ADD closed_currency_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE position ADD CONSTRAINT FK_462CE4F588D6FC7F FOREIGN KEY (allocation_currency_id) REFERENCES currency (id)');
        $this->addSql('ALTER TABLE position ADD CONSTRAINT FK_462CE4F52A4B5E6B FOREIGN KEY (closed_currency_id) REFERENCES currency (id)');
        $this->addSql('CREATE INDEX IDX_462CE4F588D6FC7F ON position (allocation_currency_id)');
        $this->addSql('CREATE INDEX IDX_462CE4F52A4B5E6B ON position (closed_currency_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE position DROP FOREIGN KEY FK_462CE4F588D6FC7F');
        $this->addSql('ALTER TABLE position DROP FOREIGN KEY FK_462CE4F52A4B5E6B');
        $this->addSql('DROP INDEX IDX_462CE4F588D6FC7F ON position');
        $this->addSql('DROP INDEX IDX_462CE4F52A4B5E6B ON position');
        $this->addSql('ALTER TABLE position DROP allocation_currency_id, DROP closed_currency_id');
    }
}
