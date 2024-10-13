<?php

namespace App\Entity;

class PositionYield
{
	public array $labels = [];
	public array $data = [];
	public array $dataSource = [];
    public float $allocated = 0.0;
	public float $sumDividends = 0.0;
	public float $sumAvgPrice = 0.0;
	public float $totalDividend = 0.0;
	public float $totalNetYearlyDividend = 0.0;
    public float $totalAvgYield = 0.0;
    public float $dividendYieldOnCost = 0.0;

}
