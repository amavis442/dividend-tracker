<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Gives monthly summaries, import records and Trading212 pie data an owner (#74).
 *
 * Existing rows belong to user 2: all current data is theirs, other accounts
 * were early multi-tenant tests (owner decision). Pie data takes the owner of
 * its pie when it has one.
 */
final class Version20261121153011 extends AbstractMigration
{
    private const EXISTING_DATA_OWNER = 2;

    public function getDescription(): string
    {
        return 'Owner for monthly summaries, import records and Trading212 pie data (#74)';
    }

    public function up(Schema $schema): void
    {
        $owner = self::EXISTING_DATA_OWNER;

        // Import records: the owner column exists; fill it and make the name unique per owner.
        $this->addSql("UPDATE import_files SET owner_id = $owner WHERE owner_id IS NULL");
        $this->addSql('DROP INDEX uniq_eb60ef975e237e06');
        $this->addSql('ALTER TABLE import_files ALTER owner_id SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX import_files_owner_name ON import_files (owner_id, name)');

        // Monthly summaries.
        $this->addSql('ALTER TABLE monthly_summary ADD user_id INT DEFAULT NULL');
        $this->addSql("UPDATE monthly_summary SET user_id = $owner");
        $this->addSql('ALTER TABLE monthly_summary ALTER user_id SET NOT NULL');
        $this->addSql('ALTER TABLE monthly_summary ADD CONSTRAINT FK_C27DDDC8A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_C27DDDC8A76ED395 ON monthly_summary (user_id)');

        // Trading212 pie data: the owner of the linked pie, otherwise the default owner.
        $this->addSql('ALTER TABLE trading212_pie_meta_data ADD user_id INT DEFAULT NULL');
        $this->addSql('UPDATE trading212_pie_meta_data md SET user_id = pie.user_id FROM pie WHERE pie.id = md.pie_id');
        $this->addSql("UPDATE trading212_pie_meta_data SET user_id = $owner WHERE user_id IS NULL");
        $this->addSql('ALTER TABLE trading212_pie_meta_data ALTER user_id SET NOT NULL');
        $this->addSql('ALTER TABLE trading212_pie_meta_data ADD CONSTRAINT FK_95275C94A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_95275C94A76ED395 ON trading212_pie_meta_data (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX import_files_owner_name');
        $this->addSql('ALTER TABLE import_files ALTER owner_id DROP NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_eb60ef975e237e06 ON import_files (name)');
        $this->addSql('ALTER TABLE monthly_summary DROP CONSTRAINT FK_C27DDDC8A76ED395');
        $this->addSql('DROP INDEX IDX_C27DDDC8A76ED395');
        $this->addSql('ALTER TABLE monthly_summary DROP user_id');
        $this->addSql('ALTER TABLE trading212_pie_meta_data DROP CONSTRAINT FK_95275C94A76ED395');
        $this->addSql('DROP INDEX IDX_95275C94A76ED395');
        $this->addSql('ALTER TABLE trading212_pie_meta_data DROP user_id');
    }
}
