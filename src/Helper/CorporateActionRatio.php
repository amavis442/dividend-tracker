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
