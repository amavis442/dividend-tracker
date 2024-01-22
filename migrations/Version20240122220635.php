<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240122220635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE transaction set stampduty = 0  WHERE stampduty IS NULL');
        $this->addSql('UPDATE transaction set transaction_fee = 0  WHERE transaction_fee IS NULL');
        $this->addSql('UPDATE transaction set finra_fee = 0  WHERE finra_fee IS NULL');


        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction ALTER stampduty TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE transaction ALTER stampduty SET DEFAULT \'0\'');
        $this->addSql('ALTER TABLE transaction ALTER stampduty SET NOT NULL');
        $this->addSql('ALTER TABLE transaction ALTER transaction_fee TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE transaction ALTER transaction_fee SET DEFAULT \'0\'');
        $this->addSql('ALTER TABLE transaction ALTER transaction_fee SET NOT NULL');
        $this->addSql('ALTER TABLE transaction ALTER finra_fee TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE transaction ALTER finra_fee SET DEFAULT \'0\'');
        $this->addSql('ALTER TABLE transaction ALTER finra_fee SET NOT NULL');

        $this->addSql('UPDATE transaction set stampduty = stampduty / 1000  WHERE stampduty > 0');
        $this->addSql('UPDATE transaction set transaction_fee = transaction_fee / 1000  WHERE transaction_fee > 0');
        $this->addSql('UPDATE transaction set finra_fee = finra_fee / 1000  WHERE finra_fee > 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE transaction set stampduty = stampduty * 1000  WHERE stampduty > 0');
        $this->addSql('UPDATE transaction set transaction_fee = transaction_fee * 1000  WHERE transaction_fee > 0');
        $this->addSql('UPDATE transaction set finra_fee = finra_fee * 1000  WHERE finra_fee > 0');

        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction ALTER stampduty TYPE INT');
        $this->addSql('ALTER TABLE transaction ALTER stampduty DROP DEFAULT');
        $this->addSql('ALTER TABLE transaction ALTER stampduty DROP NOT NULL');
        $this->addSql('ALTER TABLE transaction ALTER transaction_fee TYPE INT');
        $this->addSql('ALTER TABLE transaction ALTER transaction_fee DROP DEFAULT');
        $this->addSql('ALTER TABLE transaction ALTER transaction_fee DROP NOT NULL');
        $this->addSql('ALTER TABLE transaction ALTER finra_fee TYPE INT');
        $this->addSql('ALTER TABLE transaction ALTER finra_fee DROP DEFAULT');
        $this->addSql('ALTER TABLE transaction ALTER finra_fee DROP NOT NULL');
    }
}
