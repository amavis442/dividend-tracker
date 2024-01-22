<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240122220159 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE transaction set total = 0  WHERE total IS NULL');

        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction ALTER total TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE transaction ALTER total SET DEFAULT \'0\'');
        $this->addSql('ALTER TABLE transaction ALTER total SET NOT NULL');

        $this->addSql('UPDATE transaction set total = total / 1000  WHERE total > 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE transaction set total = total * 1000  WHERE total > 0');
        $this->addSql('ALTER TABLE transaction ALTER total TYPE INT');
        $this->addSql('ALTER TABLE transaction ALTER total DROP DEFAULT');
        $this->addSql('ALTER TABLE transaction ALTER total DROP NOT NULL');
    }
}
