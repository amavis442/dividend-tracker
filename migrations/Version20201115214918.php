<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201115214918 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE pie (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pie_position (pie_id INT NOT NULL, position_id INT NOT NULL, INDEX IDX_CD2D9529BB3AA24D (pie_id), INDEX IDX_CD2D9529DD842E46 (position_id), PRIMARY KEY(pie_id, position_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE pie_position ADD CONSTRAINT FK_CD2D9529BB3AA24D FOREIGN KEY (pie_id) REFERENCES pie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE pie_position ADD CONSTRAINT FK_CD2D9529DD842E46 FOREIGN KEY (position_id) REFERENCES position (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE attachment CHANGE research_id research_id INT DEFAULT NULL, CHANGE label label VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE branch CHANGE parent_id parent_id INT DEFAULT NULL, CHANGE asset_allocation asset_allocation INT DEFAULT NULL');
        $this->addSql('ALTER TABLE calendar CHANGE currency_id currency_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE currency CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE sign sign VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment CHANGE calendar_id calendar_id INT DEFAULT NULL, CHANGE position_id position_id INT DEFAULT NULL, CHANGE broker broker VARCHAR(40) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE amount amount BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE position CHANGE allocation_currency_id allocation_currency_id INT DEFAULT NULL, CHANGE price price INT DEFAULT NULL, CHANGE closed closed TINYINT(1) DEFAULT NULL, CHANGE profit profit INT DEFAULT NULL, CHANGE allocation allocation INT DEFAULT NULL, CHANGE posid posid VARCHAR(255) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE research CHANGE title title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ticker CHANGE isin isin VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE allocation_currency_id allocation_currency_id INT DEFAULT NULL, CHANGE position_id position_id INT DEFAULT NULL, CHANGE price price INT DEFAULT NULL, CHANGE profit profit INT DEFAULT NULL, CHANGE allocation allocation INT DEFAULT NULL, CHANGE broker broker VARCHAR(255) DEFAULT \'Trading212\' NOT NULL, CHANGE avgprice avgprice INT DEFAULT NULL, CHANGE jobid jobid VARCHAR(255) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE exchange_rate exchange_rate VARCHAR(255) DEFAULT NULL, CHANGE meta meta VARCHAR(255) DEFAULT NULL, CHANGE importfile importfile VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE pie_position DROP FOREIGN KEY FK_CD2D9529BB3AA24D');
        $this->addSql('DROP TABLE pie');
        $this->addSql('DROP TABLE pie_position');
        $this->addSql('ALTER TABLE attachment CHANGE research_id research_id INT DEFAULT NULL, CHANGE label label VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE branch CHANGE parent_id parent_id INT DEFAULT NULL, CHANGE asset_allocation asset_allocation INT DEFAULT NULL');
        $this->addSql('ALTER TABLE calendar CHANGE currency_id currency_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE currency CHANGE description description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE sign sign VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE payment CHANGE calendar_id calendar_id INT DEFAULT NULL, CHANGE position_id position_id INT DEFAULT NULL, CHANGE amount amount BIGINT DEFAULT NULL, CHANGE broker broker VARCHAR(40) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE position CHANGE allocation_currency_id allocation_currency_id INT DEFAULT NULL, CHANGE price price INT DEFAULT NULL, CHANGE closed closed TINYINT(1) DEFAULT \'NULL\', CHANGE profit profit INT DEFAULT NULL, CHANGE allocation allocation INT DEFAULT NULL, CHANGE posid posid VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE research CHANGE title title VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE ticker CHANGE isin isin VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE transaction CHANGE allocation_currency_id allocation_currency_id INT DEFAULT NULL, CHANGE position_id position_id INT DEFAULT NULL, CHANGE price price INT DEFAULT NULL, CHANGE profit profit INT DEFAULT NULL, CHANGE allocation allocation INT DEFAULT NULL, CHANGE broker broker VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'\'\'Trading212\'\'\' NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE avgprice avgprice INT DEFAULT NULL, CHANGE jobid jobid VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE exchange_rate exchange_rate VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\', CHANGE meta meta VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE importfile importfile VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user CHANGE roles roles LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
    }
}
