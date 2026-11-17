<?php

declare(strict_types=1);

namespace App\Contracts\Adjustment;

use App\Entity\CorporateAction;
use DateTime;

interface DividendAdjustmentStrategyInterface
{
    public function supports(CorporateAction $action): bool;

    public function adjustDividend(float $declaredAmount, DateTime $declarationDate, CorporateAction $action): float;
}
