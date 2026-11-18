<?php

declare(strict_types=1);

namespace App\Dto;

class PositionYield
{
    public array $dataSource = [];
    public float $sumDividends = 0.0;
    public float $sumAvgPrice = 0.0;
}
