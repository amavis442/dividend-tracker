<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190908114333 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        
        $this->addSql('ALTER TABLE payment ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6D28840DA76ED395 ON payment (user_id)');
        
        $this->addSql('ALTER TABLE branch ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE branch ADD CONSTRAINT FK_BB861B1FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_BB861B1FA76ED395 ON branch (user_id)');
        
        $this->addSql('ALTER TABLE calendar ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE calendar ADD CONSTRAINT FK_6EA9A146A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6EA9A146A76ED395 ON calendar (user_id)');
        
        $this->addSql('ALTER TABLE position ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE position ADD CONSTRAINT FK_462CE4F5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_462CE4F5A76ED395 ON position (user_id)');
        
        $this->addSql('ALTER TABLE ticker ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE ticker ADD CONSTRAINT FK_7EC30896A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_7EC30896A76ED395 ON ticker (user_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DA76ED395');
        $this->addSql('ALTER TABLE branch DROP FOREIGN KEY FK_BB861B1FA76ED395');
        $this->addSql('ALTER TABLE calendar DROP FOREIGN KEY FK_6EA9A146A76ED395');
        $this->addSql('ALTER TABLE position DROP FOREIGN KEY FK_462CE4F5A76ED395');
        $this->addSql('ALTER TABLE ticker DROP FOREIGN KEY FK_7EC30896A76ED395');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP INDEX IDX_BB861B1FA76ED395 ON branch');
        $this->addSql('ALTER TABLE branch DROP user_id');
        $this->addSql('DROP INDEX IDX_6EA9A146A76ED395 ON calendar');
        $this->addSql('ALTER TABLE calendar DROP user_id');
        $this->addSql('DROP INDEX IDX_6D28840DA76ED395 ON payment');
        $this->addSql('ALTER TABLE payment DROP user_id');
        $this->addSql('DROP INDEX IDX_462CE4F5A76ED395 ON position');
        $this->addSql('ALTER TABLE position DROP user_id');
        $this->addSql('DROP INDEX IDX_7EC30896A76ED395 ON ticker');
        $this->addSql('ALTER TABLE ticker DROP user_id');
    }
}
