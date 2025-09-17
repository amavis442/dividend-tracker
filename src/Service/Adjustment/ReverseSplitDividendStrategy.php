<?php

namespace App\Service\Adjustment;

use App\Entity\CorporateAction;
use DateTime;

class ReverseSplitDividendStrategy implements DividendAdjustmentStrategyInterface
{
    public function supports(CorporateAction $action): bool
    {
        return $action->getType() === 'reverse_split';
    }

    public function adjustDividend(float $declaredAmount, DateTime $declarationDate, CorporateAction $action): float
    {
        // Only adjust if the dividend was declared before the reverse split
        if ($declarationDate < $action->getEventDate()) {
            return $declaredAmount / $action->getRatio();
        }

        return $declaredAmount;
    }
}
