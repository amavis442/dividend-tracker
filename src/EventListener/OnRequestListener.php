<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Enables the Doctrine user filter so every query is scoped to the logged-in user.
 */
// Runs after the firewall (priority 8) and before API Platform loads data (priority 4).
#[AsEventListener(event: KernelEvents::REQUEST, method: 'onKernelRequest', priority: 5)]
class OnRequestListener
{
    public const USER_FILTER = 'user_filter';

    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        if ($user instanceof User) {
            $filter = $this->manager->getFilters()->enable(self::USER_FILTER);
            $filter->setParameter('userID', $user->getId());
        }
    }
}
