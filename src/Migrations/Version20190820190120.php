<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190820190120 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create a branch table';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSQL(
            'CREATE TABLE branch (
                id int PRIMARY KEY AUTO_INCREMENT not null,
                parent_id INT NULL DEFAULT 0,
                label varchar(255) NULL DEFAULT NULL,
                INDEX(label)
            )ENGINE=InnoDB;'
        );
    }

    public function down(Schema $schema) : void
    {
        $this->addSQL('DROP TABLE branch');
    }
}
