<?php

namespace App\EventListener;

use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;

class UpdatedAtTimestamp
{
    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        $entity = $args->getObject();
        if (method_exists(get_class($entity), 'setUpdatedAt')) {
            $entity->setUpdatedAt((new DateTime()));
        };
    }
}
