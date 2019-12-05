<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191124134541 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE file');
        $this->addSql('ALTER TABLE calendar ADD payment_id INT DEFAULT NULL, CHANGE currency_id currency_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE calendar ADD CONSTRAINT FK_6EA9A1464C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6EA9A1464C3A3BB ON calendar (payment_id)');
        $this->addSql('ALTER TABLE branch CHANGE parent_id parent_id INT DEFAULT NULL, CHANGE asset_allocation asset_allocation INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL');
        $this->addSql('ALTER TABLE research CHANGE title title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE currency CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE sign sign VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE position CHANGE currency_id currency_id INT NOT NULL, CHANGE allocation_currency_id allocation_currency_id INT DEFAULT NULL, CHANGE closed_currency_id closed_currency_id INT DEFAULT NULL, CHANGE closed closed TINYINT(1) DEFAULT NULL, CHANGE close_date close_date DATETIME DEFAULT NULL, CHANGE close_price close_price INT DEFAULT NULL, CHANGE profit profit INT DEFAULT NULL, CHANGE allocation allocation INT DEFAULT NULL, CHANGE broker broker VARCHAR(255) DEFAULT \'Trading212\' NOT NULL');
        $this->addSql('CREATE INDEX IDX_462CE4F5F7591B23 ON position (closed_currency_id)');
        $this->addSql('DROP INDEX idx_462ce4f52a4b5e6b ON position');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DDD842E46');
        $this->addSql('DROP INDEX IDX_6D28840DDD842E46 ON payment');
        $this->addSql('ALTER TABLE payment DROP position_id, CHANGE calendar_id calendar_id INT DEFAULT NULL, CHANGE currency_id currency_id INT NOT NULL, CHANGE stocks stocks INT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE file (id INT AUTO_INCREMENT NOT NULL, research_id INT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, path VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, filename VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_8C9F36107909E1ED (research_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F36107909E1ED FOREIGN KEY (research_id) REFERENCES research (id)');
        $this->addSql('ALTER TABLE branch CHANGE parent_id parent_id INT DEFAULT NULL, CHANGE asset_allocation asset_allocation INT DEFAULT NULL');
        $this->addSql('ALTER TABLE calendar DROP FOREIGN KEY FK_6EA9A1464C3A3BB');
        $this->addSql('DROP INDEX UNIQ_6EA9A1464C3A3BB ON calendar');
        $this->addSql('ALTER TABLE calendar DROP payment_id, CHANGE currency_id currency_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE currency CHANGE description description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE sign sign VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE payment ADD position_id INT DEFAULT NULL, CHANGE calendar_id calendar_id INT DEFAULT NULL, CHANGE currency_id currency_id INT DEFAULT 1 NOT NULL, CHANGE stocks stocks INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DDD842E46 FOREIGN KEY (position_id) REFERENCES position (id)');
        $this->addSql('CREATE INDEX IDX_6D28840DDD842E46 ON payment (position_id)');
        $this->addSql('ALTER TABLE position CHANGE currency_id currency_id INT DEFAULT 1 NOT NULL, CHANGE allocation_currency_id allocation_currency_id INT DEFAULT NULL, CHANGE closed_currency_id closed_currency_id INT DEFAULT NULL, CHANGE closed closed TINYINT(1) DEFAULT \'NULL\', CHANGE close_date close_date DATETIME DEFAULT \'NULL\', CHANGE close_price close_price INT DEFAULT NULL, CHANGE profit profit INT DEFAULT NULL, CHANGE allocation allocation INT DEFAULT NULL, CHANGE broker broker VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'\'\'Trading212\'\'\' NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE INDEX idx_462ce4f52a4b5e6b ON position (closed_currency_id)');
        $this->addSql('DROP INDEX IDX_462CE4F5F7591B23 ON position');
        $this->addSql('ALTER TABLE research CHANGE title title VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user CHANGE roles roles LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
    }
}
