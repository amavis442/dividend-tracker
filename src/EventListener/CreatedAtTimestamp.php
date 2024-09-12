<?php

namespace App\EventListener;

use DateTime;
use Doctrine\ORM\Event\PrePersistEventArgs;

class CreatedAtTimestamp
{
    public function prePersist(PrePersistEventArgs $args)
    {
        $entity = $args->getObject();

        $entity = $args->getObject();
        if (method_exists(get_class($entity), 'setCreatedAt')) {
            if (method_exists(get_class($entity), 'getCreatedAt') && $entity->getCreatedAt() !== null) {
                return;
            }
            //$entity->setCreatedAt((new DateTime()));
        };
    }
}
