<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250506113514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE incomes_shares_data ADD incomes_shares_data_set_id INT NULL
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE incomes_shares_data SET incomes_shares_data_set_id = (SELECT id FROM incomes_shares_data_set WHERE incomes_shares_data_set.uuid = incomes_shares_data.dataset)
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE incomes_shares_data ALTER incomes_shares_data_set_id SET  NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE incomes_shares_data ADD CONSTRAINT FK_9B3BEAC81D55ECA1 FOREIGN KEY (incomes_shares_data_set_id) REFERENCES incomes_shares_data_set (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9B3BEAC81D55ECA1 ON incomes_shares_data (incomes_shares_data_set_id)
        SQL);


    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE incomes_shares_data DROP CONSTRAINT FK_9B3BEAC81D55ECA1
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_9B3BEAC81D55ECA1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE incomes_shares_data DROP incomes_shares_data_set_id
        SQL);
    }
}
