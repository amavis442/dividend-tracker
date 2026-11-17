<?php

declare(strict_types=1);

namespace App\EventListener;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\UsageTrackingTokenStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use App\Entity\User;

class OnRequestListener
{
    private UsageTrackingTokenStorage $tokenStorage;
    private EntityManager $manager;

    public function __construct(EntityManager $manager, UsageTrackingTokenStorage $tokenStorage)
    {
        $this->manager = $manager;
        $this->tokenStorage = $tokenStorage;
    }

    public function onKernelRequest(RequestEvent $event): void
    {

        $token = $this->tokenStorage->getToken();
        if ($token != null) {
            $user = $token->getUser();
            if ($user instanceof User) {
                $filter = $this->manager->getFilters()->enable('user_filter');
                $filter->setParameter('userID', $user->getId());
            }
        }
    }
}
