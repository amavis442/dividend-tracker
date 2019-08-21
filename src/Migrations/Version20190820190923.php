<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190820190923 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create a position table';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSQL(
            'CREATE TABLE position (
                id int PRIMARY KEY AUTO_INCREMENT not null,
                ticker_id INT NOT NULL,
                price DECIMAL(10,2) default 0.0,
                amount DECIMAL(10,2) default 0.0,
                INDEX(ticker_id),
                CONSTRAINT `fk_ticker_position`
		        FOREIGN KEY (ticker_id) REFERENCES ticker (id)
		        ON DELETE CASCADE
		        ON UPDATE RESTRICT
            )ENGINE=InnoDB;'
        );
    }

    public function down(Schema $schema) : void
    {
        $this->addSQL('DROP TABLE position');
    }
}
