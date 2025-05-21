<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250521222512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the view for the gaines per trading212 pie';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
        CREATE OR REPLACE VIEW trading212_yield AS
        SELECT
            trading212_pie_id,
            EXTRACT(MONTH FROM  created_at) AS "month",
            EXTRACT(YEAR FROM created_at) AS "year",
            MIN(gained) AS "start_gained",
            MAX(gained) AS "end_gained",
            first(price_avg_invested_value) AS "start_invested",
            last(price_avg_invested_value) AS "end_invested"
            FROM trading212_pie_meta_data
            GROUP BY trading212_pie_id,EXTRACT(MONTH FROM  created_at), EXTRACT(YEAR FROM created_at);
        SQL);


    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
