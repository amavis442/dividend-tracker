<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210406154038 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment ADD tax_withold INT DEFAULT NULL, ADD tax_currency VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction ADD original_price INT DEFAULT NULL, ADD original_price_currency VARCHAR(255) DEFAULT NULL, ADD stampduty INT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment DROP tax_withold, DROP tax_currency');
        $this->addSql('ALTER TABLE transaction DROP original_price, DROP original_price_currency, DROP stampduty');
    }
}
