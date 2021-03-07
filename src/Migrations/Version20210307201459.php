<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210307201459 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE attachment CHANGE research_id research_id INT DEFAULT NULL, CHANGE label label VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE branch CHANGE parent_id parent_id INT DEFAULT NULL, CHANGE asset_allocation asset_allocation INT DEFAULT NULL');
        $this->addSql('ALTER TABLE calendar CHANGE currency_id currency_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE currency CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE sign sign VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE journal CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE title title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment DROP broker, CHANGE calendar_id calendar_id INT DEFAULT NULL, CHANGE position_id position_id INT DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE amount amount BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE position CHANGE allocation_currency_id allocation_currency_id INT DEFAULT NULL, CHANGE price price INT DEFAULT NULL, CHANGE closed closed TINYINT(1) DEFAULT NULL, CHANGE profit profit INT DEFAULT NULL, CHANGE allocation allocation INT DEFAULT NULL, CHANGE posid posid VARCHAR(255) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE closed_at closed_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE research CHANGE title title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ticker CHANGE isin isin VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction DROP broker, CHANGE allocation_currency_id allocation_currency_id INT DEFAULT NULL, CHANGE position_id position_id INT DEFAULT NULL, CHANGE price price INT DEFAULT NULL, CHANGE profit profit INT DEFAULT NULL, CHANGE allocation allocation INT DEFAULT NULL, CHANGE avgprice avgprice INT DEFAULT NULL, CHANGE jobid jobid VARCHAR(255) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE exchange_rate exchange_rate VARCHAR(255) DEFAULT NULL, CHANGE meta meta VARCHAR(255) DEFAULT NULL, CHANGE importfile importfile VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE attachment CHANGE research_id research_id INT DEFAULT NULL, CHANGE label label VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE branch CHANGE parent_id parent_id INT DEFAULT NULL, CHANGE asset_allocation asset_allocation INT DEFAULT NULL');
        $this->addSql('ALTER TABLE calendar CHANGE currency_id currency_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE currency CHANGE description description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE sign sign VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE journal CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\', CHANGE title title VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE payment ADD broker VARCHAR(40) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE calendar_id calendar_id INT DEFAULT NULL, CHANGE position_id position_id INT DEFAULT NULL, CHANGE amount amount BIGINT DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE position CHANGE allocation_currency_id allocation_currency_id INT DEFAULT NULL, CHANGE price price INT DEFAULT NULL, CHANGE closed closed TINYINT(1) DEFAULT \'NULL\', CHANGE profit profit INT DEFAULT NULL, CHANGE allocation allocation INT DEFAULT NULL, CHANGE posid posid VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\', CHANGE closed_at closed_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE research CHANGE title title VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE ticker CHANGE isin isin VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE transaction ADD broker VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'\'\'Trading212\'\'\' COLLATE `utf8mb4_unicode_ci`, CHANGE allocation_currency_id allocation_currency_id INT DEFAULT NULL, CHANGE position_id position_id INT DEFAULT NULL, CHANGE price price INT DEFAULT NULL, CHANGE profit profit INT DEFAULT NULL, CHANGE allocation allocation INT DEFAULT NULL, CHANGE avgprice avgprice INT DEFAULT NULL, CHANGE jobid jobid VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE exchange_rate exchange_rate VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\', CHANGE meta meta VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE importfile importfile VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user CHANGE roles roles LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
    }
}
