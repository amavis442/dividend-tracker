<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190920151747 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ticker_dividend_month (ticker_id INT NOT NULL, dividend_month_id INT NOT NULL, INDEX IDX_4607B06A556B180E (ticker_id), INDEX IDX_4607B06A4DC522DC (dividend_month_id), PRIMARY KEY(ticker_id, dividend_month_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dividend_month (id INT AUTO_INCREMENT NOT NULL, dividend_month INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ticker_dividend_month ADD CONSTRAINT FK_4607B06A556B180E FOREIGN KEY (ticker_id) REFERENCES ticker (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ticker_dividend_month ADD CONSTRAINT FK_4607B06A4DC522DC FOREIGN KEY (dividend_month_id) REFERENCES dividend_month (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ticker_dividend_month DROP FOREIGN KEY FK_4607B06A4DC522DC');
        $this->addSql('DROP TABLE ticker_dividend_month');
        $this->addSql('DROP TABLE dividend_month');
    }
}
