<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use App\Entity\Payment;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190830175930 extends AbstractMigration implements ContainerAwareInterface
{    
    use ContainerAwareTrait;

    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
       
        $em = $this->container->get('doctrine.orm.entity_manager');
        $paymentRepository = $em->getRepository(Payment::class);
        $payments = $paymentRepository->findAll();
        foreach ($payments as $payment)
        {
            $ticker = $payment->getPosition()->getTicker();
            $this->addSql('UPDATE payment p set p.ticker_id = '.$ticker->getId(). ' WHERE p.id = '.$payment->getId());
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('UPDATE payment p SET p.ticker_id = 1');
    }
}
