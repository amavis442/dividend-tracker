<?php

namespace App\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Security;

class AddUser
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }


    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        $user = $this->security->getUser();
        
        $entity = $args->getObject();
        if (method_exists(get_class($entity),'setUser')){
            $entity->setUser($user);
        };
    }
}
