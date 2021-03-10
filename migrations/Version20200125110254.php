<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200125110254 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE `transaction` (id INT AUTO_INCREMENT NOT NULL, currency_id INT NOT NULL, allocation_currency_id INT DEFAULT NULL, ticker_id INT NOT NULL, position_id INT DEFAULT NULL, user_id INT NOT NULL, side INT DEFAULT 1 NOT NULL, price INT DEFAULT NULL, amount INT NOT NULL, transaction_date DATETIME NOT NULL, profit INT DEFAULT NULL, allocation INT DEFAULT NULL, broker VARCHAR(255) DEFAULT \'Trading212\' NOT NULL, INDEX IDX_F529939838248176 (currency_id), INDEX IDX_F529939888D6FC7F (allocation_currency_id), INDEX IDX_F5299398556B180E (ticker_id), INDEX IDX_F5299398DD842E46 (position_id), INDEX IDX_F5299398A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `transaction` ADD CONSTRAINT FK_F529939838248176 FOREIGN KEY (currency_id) REFERENCES currency (id)');
        $this->addSql('ALTER TABLE `transaction` ADD CONSTRAINT FK_F529939888D6FC7F FOREIGN KEY (allocation_currency_id) REFERENCES currency (id)');
        $this->addSql('ALTER TABLE `transaction` ADD CONSTRAINT FK_F5299398556B180E FOREIGN KEY (ticker_id) REFERENCES ticker (id)');
        $this->addSql('ALTER TABLE `transaction` ADD CONSTRAINT FK_F5299398DD842E46 FOREIGN KEY (position_id) REFERENCES position (id)');
        $this->addSql('ALTER TABLE `transaction` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        
        $this->addSql('INSERT INTO `transaction` (`ticker_id`,`user_id`,`currency_id`,
        `allocation_currency_id`, `price`, `amount`,
        `transaction_date`, `profit`, `allocation`,
        `broker`, `side`)  SELECT `ticker_id`,`user_id`,`currency_id`,
        `allocation_currency_id`, `price`, `amount`,
        `buy_date`, `profit`, `allocation`,
        `broker`, 1 FROM `position` where closed <> 1');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE `transaction`');
    }
}
