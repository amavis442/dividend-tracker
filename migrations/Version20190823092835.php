<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190823092835 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE branch (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, label VARCHAR(255) NOT NULL, INDEX IDX_BB861B1F727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ticker (id INT AUTO_INCREMENT NOT NULL, branch_id INT NOT NULL, ticker VARCHAR(255) NOT NULL, fullname VARCHAR(255) NOT NULL, INDEX IDX_7EC30896DCD6CC49 (branch_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE position (id INT AUTO_INCREMENT NOT NULL, ticker_id INT NOT NULL, price INT NOT NULL, amount INT NOT NULL, INDEX IDX_462CE4F5556B180E (ticker_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, position_id INT DEFAULT NULL, ex_dividend_date DATETIME NOT NULL, record_date DATETIME DEFAULT NULL, pay_date DATETIME NOT NULL, dividend INT NOT NULL, INDEX IDX_6D28840DDD842E46 (position_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE branch ADD CONSTRAINT FK_BB861B1F727ACA70 FOREIGN KEY (parent_id) REFERENCES branch (id)');
        $this->addSql('ALTER TABLE ticker ADD CONSTRAINT FK_7EC30896DCD6CC49 FOREIGN KEY (branch_id) REFERENCES branch (id)');
        $this->addSql('ALTER TABLE position ADD CONSTRAINT FK_462CE4F5556B180E FOREIGN KEY (ticker_id) REFERENCES ticker (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DDD842E46 FOREIGN KEY (position_id) REFERENCES position (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE branch DROP FOREIGN KEY FK_BB861B1F727ACA70');
        $this->addSql('ALTER TABLE ticker DROP FOREIGN KEY FK_7EC30896DCD6CC49');
        $this->addSql('ALTER TABLE position DROP FOREIGN KEY FK_462CE4F5556B180E');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DDD842E46');
        $this->addSql('DROP TABLE branch');
        $this->addSql('DROP TABLE ticker');
        $this->addSql('DROP TABLE position');
        $this->addSql('DROP TABLE payment');
    }
}
