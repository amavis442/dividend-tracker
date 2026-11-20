<?php

declare(strict_types=1);

namespace App\Repository;

/**
 * Turns sort values from requests into safe DQL: only mapped, non-text fields
 * of the entity and ASC/DESC pass, anything else falls back to a default.
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

        // Long text columns are not sortable: sorting them is slow and never useful.
        $sortable = $metadata->hasField($field)
            && !in_array($metadata->getTypeOfField($field), ['text', 'blob', 'json'], true);

        return $alias . '.' . ($sortable ? $field : $default);
    }

    private static function sortDirection(string $direction, string $default = 'ASC'): string
    {
        $direction = strtoupper($direction);

        return in_array($direction, ['ASC', 'DESC'], true) ? $direction : $default;
    }
}
