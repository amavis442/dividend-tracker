<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\User;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Grants OWNER when the object's user is the logged-in user, compared by id.
 *
 * @extends Voter<string, object>
 */
final class OwnerVoter extends Voter
{
    public const OWNER = 'OWNER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::OWNER && is_object($subject) && method_exists($subject, 'getUser');
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null
    ): bool {
        $user = $token->getUser();
        try {
            $owner = $subject->getUser();
        } catch (EntityNotFoundException) {
            // The owner is hidden by the user filter, so it is someone else.
            return false;
        }

        return $user instanceof User
            && $owner instanceof User
            && $owner->getId() !== null
            && $owner->getId() === $user->getId();
    }
}
