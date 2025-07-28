<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250727170746 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Precision fix 12 for decimal and 8 after';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP VIEW IF EXISTS trading212_yield');

        $this->addSql('ALTER TABLE trading212_pie_instrument ALTER owned_quantity TYPE NUMERIC(20, 8)');
        $this->addSql('ALTER TABLE trading212_pie_instrument ALTER owned_quantity SET DEFAULT \'0.00000000\'');
        $this->addSql('ALTER TABLE trading212_pie_instrument ALTER price_avg_invested_value TYPE NUMERIC(20, 8)');
        $this->addSql('ALTER TABLE trading212_pie_instrument ALTER price_avg_invested_value SET DEFAULT \'0.00000000\'');
        $this->addSql('ALTER TABLE trading212_pie_instrument ALTER price_avg_value TYPE NUMERIC(20, 8)');
        $this->addSql('ALTER TABLE trading212_pie_instrument ALTER price_avg_value SET DEFAULT \'0.00000000\'');
        $this->addSql('ALTER TABLE trading212_pie_instrument ALTER price_avg_result TYPE NUMERIC(20, 8)');
        $this->addSql('ALTER TABLE trading212_pie_instrument ALTER price_avg_result SET DEFAULT \'0.00000000\'');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER price_avg_invested_value TYPE NUMERIC(20, 8)');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER price_avg_invested_value SET DEFAULT \'0.00000000\'');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER price_avg_value TYPE NUMERIC(20, 8)');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER price_avg_value SET DEFAULT \'0.00000000\'');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER gained TYPE NUMERIC(20, 8)');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER gained SET DEFAULT \'0.00000000\'');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER gained SET NOT NULL');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER reinvested TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER in_cash TYPE NUMERIC(20, 8)');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER in_cash SET DEFAULT \'0.00000000\'');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER in_cash SET NOT NULL');

        $this->addSql('CREATE OR REPLACE VIEW public.trading212_yield
            AS
            SELECT trading212_pie_id,
                EXTRACT(month FROM created_at) AS month,
                EXTRACT(year FROM created_at) AS year,
                min(gained) AS start_gained,
                max(gained) AS end_gained,
                first(price_avg_invested_value) AS start_invested,
                last(price_avg_invested_value) AS end_invested
            FROM trading212_pie_meta_data
            GROUP BY trading212_pie_id, (EXTRACT(month FROM created_at)), (EXTRACT(year FROM created_at));'
        );

        $this->addSql('ALTER TABLE public.trading212_yield OWNER TO dividend;');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trading212_pie_instrument ALTER owned_quantity TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE trading212_pie_instrument ALTER owned_quantity DROP DEFAULT');
        $this->addSql('ALTER TABLE trading212_pie_instrument ALTER price_avg_invested_value TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE trading212_pie_instrument ALTER price_avg_invested_value DROP DEFAULT');
        $this->addSql('ALTER TABLE trading212_pie_instrument ALTER price_avg_value TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE trading212_pie_instrument ALTER price_avg_value DROP DEFAULT');
        $this->addSql('ALTER TABLE trading212_pie_instrument ALTER price_avg_result TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE trading212_pie_instrument ALTER price_avg_result DROP DEFAULT');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER price_avg_invested_value TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER price_avg_invested_value DROP DEFAULT');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER price_avg_value TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER price_avg_value DROP DEFAULT');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER gained TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER gained DROP DEFAULT');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER gained DROP NOT NULL');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER reinvested TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER in_cash TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER in_cash DROP DEFAULT');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER in_cash DROP NOT NULL');
    }
}
