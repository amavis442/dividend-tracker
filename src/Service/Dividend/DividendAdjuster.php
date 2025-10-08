<?php

namespace App\Service\Dividend;

use Doctrine\Common\Collections\Collection;
use App\Entity\Transaction;

class DividendAdjuster
{

    public function getAdjustedDividend(float $dividend, \DateTimeImmutable $declared, Collection $actions): float
    {
        foreach ($actions as $action) {
            if ($declared < $action->getEventDate()) {
                $dividend /= $action->getRatio(); // Applies reverse split
            }
        }

        return $dividend;
    }
}
