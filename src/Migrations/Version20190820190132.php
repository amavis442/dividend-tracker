<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190820190132 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create a ticker table';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSQL(
            'CREATE TABLE ticker (
                id int PRIMARY KEY AUTO_INCREMENT not null,
                branch_id int NOT NULL,
                ticker varchar(10) NOT NULL,
                fullname varchar(255) NULL DEFAULT NULL,
                INDEX(ticker),
                CONSTRAINT `fk_branch_ticker`
		        FOREIGN KEY (branch_id) REFERENCES branch (id)
		        ON DELETE CASCADE
		        ON UPDATE RESTRICT
            )ENGINE=InnoDB;'
        );
    }

    public function down(Schema $schema) : void
    {
        $this->addSQL('DROP TABLE ticker');
    }
}
