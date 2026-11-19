<?php

declare(strict_types=1);

namespace App\Repository\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use App\Entity\Transaction;
use App\Entity\User;

/**
 * Limits owned entities to the logged-in user. Entities with a `user`
 * association are filtered on it; a transaction belongs to the owner of its
 * position.
 */
class UserFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if (!$this->hasParameter('userID')) {
            return '';
        }

        // getParameter() returns the value quoted for SQL.
        $userID = $this->getParameter('userID');

        if ($targetEntity->getName() === Transaction::class) {
            return sprintf(
                '%s.position_id IN (SELECT owned.id FROM position owned WHERE owned.user_id = %s)',
                $targetTableAlias,
                $userID
            );
        }

        if ($targetEntity->getName() !== User::class && $targetEntity->hasAssociation('user')) {
            return $targetTableAlias . '.user_id = ' . $userID;
        }

        return '';
    }
}
