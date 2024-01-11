<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240110203643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE attachment_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE branch_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE calendar_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE currency_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE dividend_month_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE dividend_tracker_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE files_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE import_files_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE journal_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE payment_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE pie_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE position_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE research_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE tax_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE taxonomy_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE ticker_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE transaction_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE attachment (id INT NOT NULL, research_id INT DEFAULT NULL, attachment_name VARCHAR(255) NOT NULL, attachment_size BIGINT DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, label VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_795FD9BB7909E1ED ON attachment (research_id)');
        $this->addSql('CREATE TABLE branch (id INT NOT NULL, parent_id INT DEFAULT NULL, label VARCHAR(255) NOT NULL, asset_allocation INT DEFAULT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BB861B1F727ACA70 ON branch (parent_id)');
        $this->addSql('CREATE TABLE calendar (id INT NOT NULL, ticker_id INT NOT NULL, currency_id INT DEFAULT NULL, ex_dividend_date DATE NOT NULL, record_date DATE NOT NULL, payment_date DATE NOT NULL, cash_amount INT NOT NULL, dividend_type VARCHAR(255) DEFAULT NULL, source VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6EA9A146556B180E ON calendar (ticker_id)');
        $this->addSql('CREATE INDEX IDX_6EA9A14638248176 ON calendar (currency_id)');
        $this->addSql('CREATE TABLE currency (id INT NOT NULL, symbol VARCHAR(10) NOT NULL, description VARCHAR(255) DEFAULT NULL, sign VARCHAR(10) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE dividend_month (id INT NOT NULL, dividend_month INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE dividend_tracker (id INT NOT NULL, user_id INT NOT NULL, sample_date DATE NOT NULL, principle INT NOT NULL, dividend INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D7336D41A76ED395 ON dividend_tracker (user_id)');
        $this->addSql('CREATE TABLE files (id INT NOT NULL, filename VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE import_files (id INT NOT NULL, name VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EB60EF975E237E06 ON import_files (name)');
        $this->addSql('CREATE TABLE journal (id INT NOT NULL, user_id INT NOT NULL, content TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C1A7E74DA76ED395 ON journal (user_id)');
        $this->addSql('CREATE TABLE journal_taxonomy (journal_id INT NOT NULL, taxonomy_id INT NOT NULL, PRIMARY KEY(journal_id, taxonomy_id))');
        $this->addSql('CREATE INDEX IDX_2DA21584478E8802 ON journal_taxonomy (journal_id)');
        $this->addSql('CREATE INDEX IDX_2DA215849557E6F6 ON journal_taxonomy (taxonomy_id)');
        $this->addSql('CREATE TABLE payment (id INT NOT NULL, ticker_id INT NOT NULL, calendar_id INT DEFAULT NULL, user_id INT NOT NULL, currency_id INT NOT NULL, position_id INT DEFAULT NULL, pay_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, dividend INT NOT NULL, amount BIGINT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, tax_withold INT DEFAULT NULL, tax_currency VARCHAR(255) DEFAULT NULL, dividend_type VARCHAR(255) DEFAULT NULL, dividend_paid INT DEFAULT NULL, dividend_paid_currency VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6D28840D556B180E ON payment (ticker_id)');
        $this->addSql('CREATE INDEX IDX_6D28840DA40A2C8 ON payment (calendar_id)');
        $this->addSql('CREATE INDEX IDX_6D28840DA76ED395 ON payment (user_id)');
        $this->addSql('CREATE INDEX IDX_6D28840D38248176 ON payment (currency_id)');
        $this->addSql('CREATE INDEX IDX_6D28840DDD842E46 ON payment (position_id)');
        $this->addSql('CREATE TABLE pie (id INT NOT NULL, user_id INT NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2257F47BA76ED395 ON pie (user_id)');
        $this->addSql('CREATE TABLE position (id INT NOT NULL, ticker_id INT NOT NULL, user_id INT NOT NULL, currency_id INT NOT NULL, allocation_currency_id INT DEFAULT NULL, price INT DEFAULT NULL, amount BIGINT NOT NULL, closed BOOLEAN DEFAULT NULL, profit INT DEFAULT NULL, allocation INT DEFAULT NULL, posid VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, closed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, dividend_treshold DOUBLE PRECISION DEFAULT NULL, max_allocation INT DEFAULT NULL, ignore_for_dividend BOOLEAN DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_462CE4F5556B180E ON position (ticker_id)');
        $this->addSql('CREATE INDEX IDX_462CE4F5A76ED395 ON position (user_id)');
        $this->addSql('CREATE INDEX IDX_462CE4F538248176 ON position (currency_id)');
        $this->addSql('CREATE INDEX IDX_462CE4F588D6FC7F ON position (allocation_currency_id)');
        $this->addSql('CREATE TABLE pie_position (position_id INT NOT NULL, pie_id INT NOT NULL, PRIMARY KEY(position_id, pie_id))');
        $this->addSql('CREATE INDEX IDX_CD2D9529DD842E46 ON pie_position (position_id)');
        $this->addSql('CREATE INDEX IDX_CD2D9529BB3AA24D ON pie_position (pie_id)');
        $this->addSql('CREATE TABLE research (id INT NOT NULL, ticker_id INT NOT NULL, title VARCHAR(255) DEFAULT NULL, info TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_57EB50C2556B180E ON research (ticker_id)');
        $this->addSql('CREATE TABLE tax (id INT NOT NULL, tax_rate INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, valid_from DATE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE taxonomy (id INT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE ticker (id INT NOT NULL, branch_id INT NOT NULL, tax_id INT DEFAULT NULL, ticker VARCHAR(255) NOT NULL, fullname VARCHAR(255) NOT NULL, isin VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7EC308962FE82D2D ON ticker (isin)');
        $this->addSql('CREATE INDEX IDX_7EC30896DCD6CC49 ON ticker (branch_id)');
        $this->addSql('CREATE INDEX IDX_7EC30896B2A824D8 ON ticker (tax_id)');
        $this->addSql('CREATE TABLE ticker_dividend_month (ticker_id INT NOT NULL, dividend_month_id INT NOT NULL, PRIMARY KEY(ticker_id, dividend_month_id))');
        $this->addSql('CREATE INDEX IDX_4607B06A556B180E ON ticker_dividend_month (ticker_id)');
        $this->addSql('CREATE INDEX IDX_4607B06A4DC522DC ON ticker_dividend_month (dividend_month_id)');
        $this->addSql('CREATE TABLE transaction (id INT NOT NULL, currency_id INT NOT NULL, allocation_currency_id INT DEFAULT NULL, position_id INT DEFAULT NULL, pie_id INT DEFAULT NULL, side INT DEFAULT 1 NOT NULL, price INT DEFAULT NULL, amount BIGINT NOT NULL, transaction_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, profit INT DEFAULT NULL, allocation INT DEFAULT NULL, avgprice INT DEFAULT NULL, jobid VARCHAR(255) DEFAULT NULL, exchange_rate VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, meta VARCHAR(255) DEFAULT NULL, importfile VARCHAR(255) DEFAULT NULL, fx_fee INT DEFAULT NULL, original_price INT DEFAULT NULL, original_price_currency VARCHAR(255) DEFAULT NULL, stampduty INT DEFAULT NULL, transaction_fee INT DEFAULT NULL, finra_fee INT DEFAULT NULL, total INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_723705D138248176 ON transaction (currency_id)');
        $this->addSql('CREATE INDEX IDX_723705D188D6FC7F ON transaction (allocation_currency_id)');
        $this->addSql('CREATE INDEX IDX_723705D1DD842E46 ON transaction (position_id)');
        $this->addSql('CREATE INDEX IDX_723705D1BB3AA24D ON transaction (pie_id)');
        $this->addSql('CREATE INDEX IDX_723705D1D7F2143548DD09DB ON transaction (meta, transaction_date)');
        $this->addSql('CREATE TABLE users (id INT NOT NULL, api_token VARCHAR(255) DEFAULT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E97BA2F5EB ON users (api_token)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB7909E1ED FOREIGN KEY (research_id) REFERENCES research (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE branch ADD CONSTRAINT FK_BB861B1F727ACA70 FOREIGN KEY (parent_id) REFERENCES branch (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE calendar ADD CONSTRAINT FK_6EA9A146556B180E FOREIGN KEY (ticker_id) REFERENCES ticker (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE calendar ADD CONSTRAINT FK_6EA9A14638248176 FOREIGN KEY (currency_id) REFERENCES currency (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dividend_tracker ADD CONSTRAINT FK_D7336D41A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE journal ADD CONSTRAINT FK_C1A7E74DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE journal_taxonomy ADD CONSTRAINT FK_2DA21584478E8802 FOREIGN KEY (journal_id) REFERENCES journal (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE journal_taxonomy ADD CONSTRAINT FK_2DA215849557E6F6 FOREIGN KEY (taxonomy_id) REFERENCES taxonomy (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D556B180E FOREIGN KEY (ticker_id) REFERENCES ticker (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DA40A2C8 FOREIGN KEY (calendar_id) REFERENCES calendar (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D38248176 FOREIGN KEY (currency_id) REFERENCES currency (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DDD842E46 FOREIGN KEY (position_id) REFERENCES position (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pie ADD CONSTRAINT FK_2257F47BA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE position ADD CONSTRAINT FK_462CE4F5556B180E FOREIGN KEY (ticker_id) REFERENCES ticker (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE position ADD CONSTRAINT FK_462CE4F5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE position ADD CONSTRAINT FK_462CE4F538248176 FOREIGN KEY (currency_id) REFERENCES currency (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE position ADD CONSTRAINT FK_462CE4F588D6FC7F FOREIGN KEY (allocation_currency_id) REFERENCES currency (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pie_position ADD CONSTRAINT FK_CD2D9529DD842E46 FOREIGN KEY (position_id) REFERENCES position (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pie_position ADD CONSTRAINT FK_CD2D9529BB3AA24D FOREIGN KEY (pie_id) REFERENCES pie (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE research ADD CONSTRAINT FK_57EB50C2556B180E FOREIGN KEY (ticker_id) REFERENCES ticker (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticker ADD CONSTRAINT FK_7EC30896DCD6CC49 FOREIGN KEY (branch_id) REFERENCES branch (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticker ADD CONSTRAINT FK_7EC30896B2A824D8 FOREIGN KEY (tax_id) REFERENCES tax (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticker_dividend_month ADD CONSTRAINT FK_4607B06A556B180E FOREIGN KEY (ticker_id) REFERENCES ticker (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticker_dividend_month ADD CONSTRAINT FK_4607B06A4DC522DC FOREIGN KEY (dividend_month_id) REFERENCES dividend_month (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D138248176 FOREIGN KEY (currency_id) REFERENCES currency (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D188D6FC7F FOREIGN KEY (allocation_currency_id) REFERENCES currency (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1DD842E46 FOREIGN KEY (position_id) REFERENCES position (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1BB3AA24D FOREIGN KEY (pie_id) REFERENCES pie (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE attachment_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE branch_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE calendar_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE currency_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE dividend_month_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE dividend_tracker_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE files_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE import_files_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE journal_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE payment_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE pie_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE position_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE research_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE tax_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE taxonomy_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE ticker_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE transaction_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE users_id_seq CASCADE');
        $this->addSql('ALTER TABLE attachment DROP CONSTRAINT FK_795FD9BB7909E1ED');
        $this->addSql('ALTER TABLE branch DROP CONSTRAINT FK_BB861B1F727ACA70');
        $this->addSql('ALTER TABLE calendar DROP CONSTRAINT FK_6EA9A146556B180E');
        $this->addSql('ALTER TABLE calendar DROP CONSTRAINT FK_6EA9A14638248176');
        $this->addSql('ALTER TABLE dividend_tracker DROP CONSTRAINT FK_D7336D41A76ED395');
        $this->addSql('ALTER TABLE journal DROP CONSTRAINT FK_C1A7E74DA76ED395');
        $this->addSql('ALTER TABLE journal_taxonomy DROP CONSTRAINT FK_2DA21584478E8802');
        $this->addSql('ALTER TABLE journal_taxonomy DROP CONSTRAINT FK_2DA215849557E6F6');
        $this->addSql('ALTER TABLE payment DROP CONSTRAINT FK_6D28840D556B180E');
        $this->addSql('ALTER TABLE payment DROP CONSTRAINT FK_6D28840DA40A2C8');
        $this->addSql('ALTER TABLE payment DROP CONSTRAINT FK_6D28840DA76ED395');
        $this->addSql('ALTER TABLE payment DROP CONSTRAINT FK_6D28840D38248176');
        $this->addSql('ALTER TABLE payment DROP CONSTRAINT FK_6D28840DDD842E46');
        $this->addSql('ALTER TABLE pie DROP CONSTRAINT FK_2257F47BA76ED395');
        $this->addSql('ALTER TABLE position DROP CONSTRAINT FK_462CE4F5556B180E');
        $this->addSql('ALTER TABLE position DROP CONSTRAINT FK_462CE4F5A76ED395');
        $this->addSql('ALTER TABLE position DROP CONSTRAINT FK_462CE4F538248176');
        $this->addSql('ALTER TABLE position DROP CONSTRAINT FK_462CE4F588D6FC7F');
        $this->addSql('ALTER TABLE pie_position DROP CONSTRAINT FK_CD2D9529DD842E46');
        $this->addSql('ALTER TABLE pie_position DROP CONSTRAINT FK_CD2D9529BB3AA24D');
        $this->addSql('ALTER TABLE research DROP CONSTRAINT FK_57EB50C2556B180E');
        $this->addSql('ALTER TABLE ticker DROP CONSTRAINT FK_7EC30896DCD6CC49');
        $this->addSql('ALTER TABLE ticker DROP CONSTRAINT FK_7EC30896B2A824D8');
        $this->addSql('ALTER TABLE ticker_dividend_month DROP CONSTRAINT FK_4607B06A556B180E');
        $this->addSql('ALTER TABLE ticker_dividend_month DROP CONSTRAINT FK_4607B06A4DC522DC');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D138248176');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D188D6FC7F');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D1DD842E46');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D1BB3AA24D');
        $this->addSql('DROP TABLE attachment');
        $this->addSql('DROP TABLE branch');
        $this->addSql('DROP TABLE calendar');
        $this->addSql('DROP TABLE currency');
        $this->addSql('DROP TABLE dividend_month');
        $this->addSql('DROP TABLE dividend_tracker');
        $this->addSql('DROP TABLE files');
        $this->addSql('DROP TABLE import_files');
        $this->addSql('DROP TABLE journal');
        $this->addSql('DROP TABLE journal_taxonomy');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE pie');
        $this->addSql('DROP TABLE position');
        $this->addSql('DROP TABLE pie_position');
        $this->addSql('DROP TABLE research');
        $this->addSql('DROP TABLE tax');
        $this->addSql('DROP TABLE taxonomy');
        $this->addSql('DROP TABLE ticker');
        $this->addSql('DROP TABLE ticker_dividend_month');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE users');
    }
}
