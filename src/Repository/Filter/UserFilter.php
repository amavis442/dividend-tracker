<?php

namespace App\Repository\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use App\Entity\User;

class UserFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if ($targetEntity->getName() !== User::class && $targetEntity->hasAssociation('user') && $this->hasParameter('userID')) {
            $userID = $this->getParameter('userID');
            return $targetTableAlias . ".user_id = " . $userID;
        }
        return '';
    }
}
