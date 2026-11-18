<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\CorporateAction;

/**
 * Guards calculations against an unusable corporate action.
 */
final class CorporateActionRatio
{
    /**
     * Whether something dated $date comes before a split that has taken place.
     */
    public static function appliesTo(CorporateAction $action, \DateTimeInterface $date): bool
    {
        $eventDate = $action->getEventDate();

        return $date < $eventDate && $eventDate <= new \DateTimeImmutable();
    }

    /**
     * @throws \InvalidArgumentException when the ratio is not positive
     */
    public static function of(CorporateAction $action): float
    {
        $ratio = (float) $action->getRatio();
        if ($ratio <= 0.0) {
            throw new \InvalidArgumentException('A corporate action needs a ratio above 0.');
        }

        return $ratio;
    }
}
