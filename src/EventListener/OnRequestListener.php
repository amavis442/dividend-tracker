<?php

namespace App\EventListener;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\UsageTrackingTokenStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use App\Entity\User;

class OnRequestListener
{
    private $tokenStorage;
    private $em;

    public function __construct(EntityManager $em, UsageTrackingTokenStorage $tokenStorage)
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if($this->tokenStorage->getToken()) {
            $user = $this->tokenStorage->getToken()->getUser();
            if ($user instanceof User){
                $filter = $this->em->getFilters()->enable('user_filter');
                $filter->setParameter('userID', $user->getId());
            }
        }
    }
}