<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240113223534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX IDX_TICKER_LOWER_TICKER ON ticker (LOWER(ticker));');
        $this->addSql('CREATE INDEX IDX_TICKER_LOWER_FULLNAME ON ticker (LOWER(fullname));');
        $this->addSql('CREATE INDEX IDX_TICKER_LOWER_ISIN ON ticker (LOWER(isin));');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_TICKER_LOWER_TICKER;');
        $this->addSql('DROP INDEX IDX_TICKER_LOWER_FULLNAME;');
        $this->addSql('DROP INDEX IDX_TICKER_LOWER_ISIN');
    }
}
