<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Pie;
use App\Repository\Trading212PieMetaDataRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Links Trading212 pie metadata to a pie once it has an id.
 */
#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Pie::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Pie::class)]
final class PieListener
{
    public function __construct(
        private Trading212PieMetaDataRepository $trading212PieMetaDataRepository,
    ) {
    }

    public function postPersist(Pie $pie, PostPersistEventArgs $event): void
    {
        $this->trading212PieMetaDataRepository->updatePie($pie);
    }

    public function postUpdate(Pie $pie, PostUpdateEventArgs $event): void
    {
        $this->trading212PieMetaDataRepository->updatePie($pie);
    }
}
