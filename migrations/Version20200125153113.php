<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200125153113 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `transaction` DROP CONSTRAINT FK_F5299398DD842E46');
        $this->addSql('TRUNCATE `position`');
        $this->addSql('INSERT INTO `position` (ticker_id, amount, buy_date, allocation, allocation_currency_id, currency_id, price, user_id)
        SELECT ticker_id, SUM(amount) as amount, NOW(), SUM(allocation) as allocation, allocation_currency_id, currency_id, AVG(price) price, user_id FROM
        `transaction` GROUP BY ticker_id
        ');
        $this->addSql('UPDATE `transaction` t, `position` p SET t.position_id = p.id WHERE t.ticker_id = p.ticker_id ');
        $this->addSql('ALTER TABLE `transaction` ADD CONSTRAINT FK_F5299398DD842E46 FOREIGN KEY (position_id) REFERENCES position (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
