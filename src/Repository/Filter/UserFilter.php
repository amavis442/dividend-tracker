<?php

declare(strict_types=1);

namespace App\Repository\Filter;

use App\Entity\Trading212PieInstrument;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Limits owned entities to the logged-in user.
 *
 * - An entity with a `user` or `owner` association is filtered on that column.
 * - An entity listed in OWNED_THROUGH has no owner column of its own; it
 *   belongs to the owner of the row it points to.
 * - Every other entity is shared (tickers, currencies, research, ...).
 */
class UserFilter extends SQLFilter
{
    /** Associations that name the owning user directly. */
    private const OWNER_ASSOCIATIONS = ['user', 'owner'];

    /**
     * Entity => [foreign key column, table that holds the owner in `user_id`].
     */
    private const OWNED_THROUGH = [
        Transaction::class => ['position_id', 'position'],
        Trading212PieInstrument::class => ['trading212_pie_meta_data_id', 'trading212_pie_meta_data'],
    ];

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if (!$this->hasParameter('userID')) {
            return '';
        }

        // getParameter() returns the value quoted for SQL.
        $userID = $this->getParameter('userID');
        $entity = $targetEntity->getName();

        if (isset(self::OWNED_THROUGH[$entity])) {
            [$column, $ownerTable] = self::OWNED_THROUGH[$entity];

            return sprintf(
                '%s.%s IN (SELECT owned.id FROM %s owned WHERE owned.user_id = %s)',
                $targetTableAlias,
                $column,
                $ownerTable,
                $userID
            );
        }

        if ($entity === User::class) {
            return '';
        }

        foreach (self::OWNER_ASSOCIATIONS as $association) {
            if ($targetEntity->hasAssociation($association)) {
                return sprintf(
                    '%s.%s = %s',
                    $targetTableAlias,
                    $targetEntity->getSingleAssociationJoinColumnName($association),
                    $userID
                );
            }
        }

        return '';
    }
}
