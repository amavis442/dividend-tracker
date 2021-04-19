<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210419150423 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {

        $this->addSql('UPDATE `ticker` set `isin`="NVT00002" where id = 2');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000012" where id = 12');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000015" where id = 15');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000017" where id = 17');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000020" where id = 20');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000022" where id = 22');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000026" where id = 26');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000028" where id = 28');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000033" where id = 33');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000034" where id = 34');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000038" where id = 38');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000040" where id = 40');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000042" where id = 42');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000043" where id = 43');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000045" where id = 45');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000047" where id = 47');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000050" where id = 50');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000056" where id = 56');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000057" where id = 57');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000061" where id = 61');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000063" where id = 63');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000065" where id = 65');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000070" where id = 70');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000071" where id = 71');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000074" where id = 74');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000082" where id = 82');
        $this->addSql('UPDATE `ticker` set `isin`="NVT000087" where id = 87');
        $this->addSql('UPDATE `ticker` set `isin`="NVT0000100" where id = 100');
      
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_7EC308967EC30896 ON ticker');
        $this->addSql('ALTER TABLE ticker CHANGE isin isin VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7EC308962FE82D2D ON ticker (isin)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_7EC308962FE82D2D ON ticker');
        $this->addSql('ALTER TABLE ticker CHANGE isin isin VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7EC308967EC30896 ON ticker (ticker)');
    }
}
