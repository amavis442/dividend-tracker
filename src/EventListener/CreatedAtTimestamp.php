<?php

namespace App\EventListener;

use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;

class CreatedAtTimestamp
{
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        $entity = $args->getObject();
        if (method_exists(get_class($entity), 'setCreatedAt')) {
            if (method_exists(get_class($entity), 'getCreatedAt') && $entity->getCreatedAt() !== null) {
                return;
            }
            $entity->setCreatedAt((new DateTime()));
        };
    }
}
