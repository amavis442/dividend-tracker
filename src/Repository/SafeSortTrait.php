<?php

declare(strict_types=1);

namespace App\Repository;

/**
 * Turns sort values from requests into safe DQL: only mapped fields of the
 * entity and ASC/DESC pass, anything else falls back to a default.
 */
trait SafeSortTrait
{
    /**
     * @param class-string|null $entity the entity behind $alias; the repository's own entity when null
     */
    private function sortField(string $field, string $alias, string $default, ?string $entity = null): string
    {
        $metadata = $entity === null
            ? $this->getClassMetadata()
            : $this->getEntityManager()->getClassMetadata($entity);

        return $alias . '.' . ($metadata->hasField($field) ? $field : $default);
    }

    private static function sortDirection(string $direction, string $default = 'ASC'): string
    {
        $direction = strtoupper($direction);

        return in_array($direction, ['ASC', 'DESC'], true) ? $direction : $default;
    }
}
