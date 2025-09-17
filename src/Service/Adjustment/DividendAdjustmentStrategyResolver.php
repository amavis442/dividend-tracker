<?php

namespace App\Service\Adjustment;

use App\Entity\CorporateAction;
use DateTime;

class DividendAdjustmentStrategyResolver
{
    /**
     * @param DividendAdjustmentStrategyInterface[] $strategies
     */
    public function __construct(private iterable $strategies) {}

    public function resolve(float $declaredAmount, DateTime $declarationDate, CorporateAction $action): float
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($action)) {
                return $strategy->adjustDividend($declaredAmount, $declarationDate, $action);
            }
        }

        return $declaredAmount; // fallback
    }
}
