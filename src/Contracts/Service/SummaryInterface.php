<?php

namespace App\Contracts\Service;

use App\Entity\Summary;

interface SummaryInterface
{
    public function getSummary(): Summary;
    public function getTotalAllocated(): float;
}
