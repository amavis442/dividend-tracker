<?php

namespace App\EventListener;

use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use App\Entity\Pie;
use App\Repository\Trading212PieMetaDataRepository;

#[
    AsEntityListener(
        event: Events::postPersist,
        method: 'postUpdate',
        entity: Pie::class
    )
]
final class PieListener
{

    public function __construct(
        private Trading212PieMetaDataRepository $trading212PieMetaDataRepository,
    )
    { }


    public function postUpdate(
        Pie $pie
    ): void {
        $this->trading212PieMetaDataRepository->updatePie($pie);
    }
}
