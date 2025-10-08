<?php

namespace App\EventListener;

use App\Entity\CorporateAction;
use App\Service\MetricsUpdateService;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;

#[
	AsEntityListener(
		event: Events::prePersist,
		method: 'prePersist',
		entity: CorporateAction::class
	)
]
final class CorporateActionListener
{
	public function __construct(private MetricsUpdateService $metricsUpdate)
	{
	}

	public function prePersist(
		CorporateAction $action,
		PrePersistEventArgs $event
	): void {
		$ticker = $action->getTicker();
		$position = $ticker->getPositions()->first();
		if ($position) {
			$this->metricsUpdate->update($position);
		}
	}
}
