<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190821173851 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add branches';
    }

    public function up(Schema $schema) : void
    {
        $this->addSQL(
            "INSERT INTO branch (label) values
            ('Technologie'),
            ('Consumptiegoederen'),
            ('Diensten'),
            ('Financieel'),
            ('Gezondheidszorg'),
            ('Basismaterialen') 
            ;");

    }

    public function down(Schema $schema) : void
    {
        $this->addSQL('TRUNCATE TABLE branch'); 

    }
}
