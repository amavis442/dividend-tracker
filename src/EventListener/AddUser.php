<?php

namespace App\EventListener;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

class AddUser
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }


    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        $user = $this->security->getUser();

        if (method_exists(get_class($entity), 'setUser')) {
            if (method_exists(get_class($entity), 'getUser') && $entity->getUser() !== null) {
                return;
            }
            $entity->setUser($user);
        };

        if (method_exists(get_class($entity), 'setOwner')) {
            if (method_exists(get_class($entity), 'getOwner') && $entity->getOwner() !== null) {
                return;
            }
            $entity->setOwner($user);
        };
    }
}
