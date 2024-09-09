<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240909182333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {

        $this->addSql('UPDATE transaction SET total_currency_id = 2 WHERE total_currency_id IS NULL;');

        $this->addSql('UPDATE transaction SET currency_original_price_id = 1 WHERE currency_original_price_id IS NULL;');

        // this up() migration is auto-generated, please modify it to your needs

    }

    public function down(Schema $schema): void {}
}
