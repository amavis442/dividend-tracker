<?php
namespace App\Service\Adjustment;

use App\Entity\CorporateAction;
use DateTime;

interface DividendAdjustmentStrategyInterface
{
    public function supports(CorporateAction $action): bool;

    public function adjustDividend(float $declaredAmount, DateTime $declarationDate, CorporateAction $action): float;
}
