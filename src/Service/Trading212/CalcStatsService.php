<?php

namespace App\Service\Trading212;

use App\Entity\Trading212PieMetaData;

class CalcStatsService {

	public function calc(array|Trading212PieMetaData $data): array
	{
		$totalInvested = 0.0;
		$totalValue = 0.0;
		$totalGained = 0.0;
		$totalGainedYield = 0.0;
		$totalReturn = 0.0;
		$totalReturnYield = 0.0;
		$profitLoss = 0.0;
		$profitLossPercentage = 0.0;

		if ($data instanceof Trading212PieMetaData) {
			$totalInvested = $data->getPriceAvgInvestedValue();
			$totalValue = $data->getPriceAvgValue();
			$totalGained = $data->getGained();
		}
		if (is_array($data)) {
			foreach ($data as $item) {
				$totalInvested += $item->getPriceAvgInvestedValue();
				$totalValue += $item->getPriceAvgValue();
				$totalGained += $item->getGained();
			}
		}
		$totalReturn = $totalValue + $totalGained - $totalInvested;
		$totalReturnYield =
			$totalInvested > 0 ? ($totalReturn / $totalInvested) * 100 : 0.0;
		$totalGainedYield =
			$totalInvested > 0 ? ($totalGained / $totalInvested) * 100 : 0.0;
		$profitLoss = $totalValue - $totalInvested;
		$profitLossPercentage =
			$totalInvested > 0 ? ($profitLoss / $totalInvested) * 100 : 0.0;
		return [
			'totalInvested' => $totalInvested,
			'totalValue' => $totalValue,
			'totalGained' => $totalGained,
			'totalGainedYield' => $totalGainedYield,
			'totalReturn' => $totalReturn,
			'totalReturnYield' => $totalReturnYield,
			'profitLoss' => $profitLoss,
			'profitLossPercentage' => $profitLossPercentage,
		];
	}
}
