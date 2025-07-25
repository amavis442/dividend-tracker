<?php

namespace App\EventListener;

use App\Entity\Transaction;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use App\Service\MetricsUpdateService;


#[
	AsEntityListener(
		event: Events::postPersist,
		method: 'postPersist',
		entity: Transaction::class
	)
]
final class TransactionListener
{
	public function __construct(
		private MetricsUpdateService $metricsUpdate
	) {
	}

	public function postPersist(
		Transaction $transaction,
		PostPersistEventArgs $event
	): void {
		$position = $transaction->getPosition();
		if ($position) {
            $this->metricsUpdate->update($position);
        }
	}
}
