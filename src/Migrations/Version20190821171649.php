<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190821171649 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create a dividend table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSQL(
            'CREATE TABLE payments (
                id int PRIMARY KEY AUTO_INCREMENT NOT NULL,
                position_id int NOT NULL,
                exdate DATE,
                paydate DATE null DEFAULT null,
                dividend DECIMAL(10,2) DEFAULT 0.0,
                CONSTRAINT `fk_position_payments`
		FOREIGN KEY (position_id) REFERENCES position (id)
		ON DELETE CASCADE
		ON UPDATE RESTRICT
            )ENGINE=InnoDB;'
        );
    }

    public function down(Schema $schema) : void
    {
        $this->addSQL('DROP TABLE payments');

    }
}
