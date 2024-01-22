<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240122221212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE transaction set avgprice = 0  WHERE avgprice IS NULL');
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction ALTER avgprice TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE transaction ALTER avgprice SET DEFAULT \'0\'');
        $this->addSql('ALTER TABLE transaction ALTER avgprice SET NOT NULL');
        $this->addSql('UPDATE transaction set avgprice = avgprice / 1000  WHERE avgprice > 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE transaction set avgprice = avgprice * 1000  WHERE avgprice > 0');
        $this->addSql('ALTER TABLE transaction ALTER avgprice TYPE INT');
        $this->addSql('ALTER TABLE transaction ALTER avgprice DROP DEFAULT');
        $this->addSql('ALTER TABLE transaction ALTER avgprice DROP NOT NULL');
    }
}
