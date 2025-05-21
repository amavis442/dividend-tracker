<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250521221658 extends AbstractMigration
{
	public function getDescription(): string
	{
		return 'Creates the ag functions first() and last()';
	}

	public function up(Schema $schema): void
	{
		$this->addSql(<<<'SQL'
            -- Create a function that always returns the first non-NULL value:
            CREATE OR REPLACE FUNCTION public.first_agg (anyelement, anyelement)
                RETURNS anyelement
                LANGUAGE sql IMMUTABLE STRICT PARALLEL SAFE AS
                'SELECT $1';
        SQL);

		$this->addSql(<<<'SQL'
            -- Then wrap an aggregate around it:
            CREATE OR REPLACE  AGGREGATE public.first (anyelement) (
                SFUNC    = public.first_agg
                , STYPE    = anyelement
                , PARALLEL = safe
            );
        SQL);

		$this->addSql(<<<'SQL'
            -- Create a function that always returns the last non-NULL value:
            CREATE OR REPLACE FUNCTION public.last_agg (anyelement, anyelement)
                RETURNS anyelement
                LANGUAGE sql IMMUTABLE STRICT PARALLEL SAFE AS
                'SELECT $2';
        SQL);

		$this->addSql(<<<'SQL'
            -- Then wrap an aggregate around it:
            CREATE OR REPLACE AGGREGATE public.last (anyelement) (
                SFUNC    = public.last_agg
                , STYPE    = anyelement
                , PARALLEL = safe
            );
        SQL);
	}

	public function down(Schema $schema): void
	{
		// this down() migration is auto-generated, please modify it to your needs
	}
}
