<?php

namespace App\Model;

use App\Entity\CalcCompound;
use App\Entity\Compound;


class CompoundCalculator
{
	public function run(Compound $compound): array
	{
		$data = [];
		$dividend = $compound->getDividend();
		$startAmount = $compound->getAmount();
		$startPrice = $compound->getPrice();
		$priceAppreciation = $compound->getPriceAppreciation();
		$dividendGrowthRate = $compound->getGrowth();
		$dividendGrowthRateAfter5Years = $compound->getGrowthAfter5Years();
		$maxPrice = $compound->getMaxPrice();
		$payoutFrequency = $compound->getFrequency();
		$startDividend = $dividend / $payoutFrequency;
		$years = $compound->getYears();
		$startCapital = $startAmount * $startPrice;
		$endCapital = 0.0;
		$extraMoney = $compound->getExtraPerMonth();

		$oldShares = $startAmount;
		$dividendShares = 0;
		$oldPrice = $startPrice;
		$oldDividend = (float) $startDividend;
		$dividend = (float) $startDividend;
		$year = 0;
		$quator = 0;
		$startYear = (int) date('Y');
		if (date('m') > 9) {
			$startYear++;
		}
		$reportRange = $years * $payoutFrequency;

		for ($i = 0; $i < $reportRange; $i++) {
			$data[$i]['quator'] = '';
			$data[$i]['amount'] = $oldShares;
			$data[$i]['dividend'] = 0.0;
			$data[$i]['net_dividend'] = 0.0;
			$data[$i]['new_amount'] = 0.0;
			$data[$i]['extra_dividend'] = 0.0;
			$data[$i]['shareprice'] = $startPrice;
			$data[$i]['yoc'] = 0.0;
			$data[$i]['capital'] = 0.0;
			$data[$i]['received_dividend'] = 0.0;
			$data[$i]['dividendyield'] = 0.0;
			$data[$i]['extra_per_month'] = $extraMoney;

			if ($quator > $payoutFrequency - 1) {
				$year++;
				$quator = 0;
				if ($priceAppreciation && $priceAppreciation > 0) {
					$price = $oldPrice * (1 + $priceAppreciation / 100);
					if ($maxPrice && $price > $maxPrice) {
						$price = $maxPrice;
					}
					$data[$i]['shareprice'] = $price;
					$oldPrice = $price;
				}

				if ($year > 4 && $dividendGrowthRateAfter5Years > 0) {
					$dividendGrowthRate = $dividendGrowthRateAfter5Years;
				}
				$oldDividend = $dividend;
			} else {
				if ($year > 0) {
					$data[$i]['shareprice'] = $oldPrice;
				}
			}

			if ($year > 0) {
				$dividend =
					(float) $oldDividend * (1 + $dividendGrowthRate / 100);
			}
			$data[$i]['dividendGrowth'] = $dividendGrowthRate;
			$data[$i]['dividend'] = $dividend;
			$netDividend =
				($dividend * ((100 - $compound->getTaxRate()) / 100)) /
				$compound->getExchangeRate();

			$data[$i]['net_dividend'] = $oldShares * $netDividend;
			$data[$i]['received_dividend'] = $data[$i]['net_dividend'];
			if ($i > 0) {
				$data[$i]['received_dividend'] +=
					$data[$i - 1]['received_dividend'];
			}
			$newShares =
				($data[$i]['net_dividend'] + $data[$i]['extra_per_month']) /
				$data[$i]['shareprice'];
			$data[$i]['new_amount'] = $newShares;

			$data[$i]['extra_dividend'] = $newShares * $netDividend;

			$dividendShares = $oldShares;
			$oldShares += $newShares;
			$data[$i]['quator'] = $startYear + $year;
			if ($payoutFrequency === 4) {
				$data[$i]['quator'] .= 'Q';
			}
			if ($payoutFrequency === 12) {
				$data[$i]['quator'] .= 'M';
			}
			$data[$i]['quator'] .= $quator + 1;

			$netDividendPerShare = $data[$i]['net_dividend'] / $dividendShares;
			$netDividendPerSharePerYear =
				$netDividendPerShare * $payoutFrequency;

			$data[$i]['dividendyield'] =
				($netDividendPerSharePerYear / $data[$i]['shareprice']) * 100;

			$dividendShares = $oldShares;

			//$data[$i]['yoc'] = $data[$i]['net_dividend'] / ($oldShares )

			$quator++;
		}

		return $data;
	}

	public function calc(CalcCompound $compound): array
	{
		$data = [];
        $yearlySummary = [];

		$dividendPercentage = $compound->getDividendPercentage();
		$invested = $compound->getInvested();
		$investPerMonth = $compound->getInvestPerMonth();
		$inflation = $compound->getInflation();
		$frequency = $compound->getFrequency();
		$years = $compound->getYears();
		$taxRate = $compound->getTaxRate();

		$capital = $invested;
		$startYear = (int) date('Y');
		if (date('m') > 9) {
			$startYear++;
		}
		$quator = date('m');
		$year = 0;
		$reportRange = $years * $frequency;
		for ($i = 0; $i < $reportRange; $i++) {
			$capital += $investPerMonth;
			$data[$i]['capital_before'] = $capital;
            $data[$i]['quator'] = '';

            if ($dividendPercentage > 0) {
			    $dividend = $capital * ($dividendPercentage / 100 / $frequency);
            } else {
                $dividend = 0;
            }
			$capital += $dividend;
			$data[$i]['dividend'] = $dividend;
			$data[$i]['capital_after'] = $capital;

            $data[$i]['acumulated_dividend'] = $dividend;
            if ($i > 0) {
                $data[$i]['acumulated_dividend'] += $data[$i - 1]['acumulated_dividend'];
            }

			if ($quator > $frequency - 1) {
                $periodYear = $startYear + $year;
                $yearlySummary[$periodYear]['capital'] = $data[$i-1]['capital_after'];
                $yearlySummary[$periodYear]['acumulated_dividend'] = $data[$i-1]['acumulated_dividend'];
                $yearlySummary[$periodYear]['dividend'] = $data[$i-1]['dividend'];
				$year++;
				$quator = 0;
			}

			$data[$i]['quator'] = $startYear + $year;
			if ($frequency === 4) {
				$data[$i]['quator'] .= 'Q';
			}
			if ($frequency === 12) {
				$data[$i]['quator'] .= 'M';
			}
			$data[$i]['quator'] .= $quator + 1;

			$quator++;
		}

		return ['data' => $data, 'yearlySummary' => $yearlySummary];
	}
}
