<?php

namespace App\Model;

use App\Entity\Compound;

class CompoundCalculator
{



    public function run(Compound $compound): array
    {
        $data = [];
        $dividend = $compound->getDividend() / 1000;
        $startAmount = $compound->getAmount();
        $startPrice = $compound->getPrice() / 1000;
        $priceAppreciation = $compound->getPriceAppreciation() / 1000;
        $dividendGrowthRate = $compound->getGrowth() / 1000;
        $dividendGrowthRateAfter5Years = $compound->getGrowthAfter5Years() / 1000;
        $maxPrice = $compound->getMaxPrice() / 1000;
        $payoutFrequency = $compound->getFrequency();
        $startDividend = $dividend / $payoutFrequency;
        $years = $compound->getYears();
        $startCapital = $startAmount * $startPrice;
        $endCapital = 0.0;

        $oldShares = $startAmount / 10000000;
        $oldPrice = $startPrice;
        $oldDividend = (float) $startDividend;
        $dividend = (float) $startDividend;
        $year = 0;
        $quator = 0;
        $startYear = date('Y');
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

            if ($quator > ($payoutFrequency - 1)) {
                $year++;
                $quator = 0;
                if ($priceAppreciation && $priceAppreciation > 0) {
                    $price = $oldPrice * (1 + ($priceAppreciation / 100));
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
                $dividend = (float) ($oldDividend) * (1 + ($dividendGrowthRate / 100));
            }
            $data[$i]['dividendGrowth'] = $dividendGrowthRate;
            $data[$i]['dividend'] = $dividend;
            $netDividend = (($dividend * ((100 - $compound->getTaxRate()) / 100) / $compound->getExchangeRate()));

            
            $data[$i]['net_dividend'] = $oldShares * $netDividend;
            $data[$i]['received_dividend'] = $data[$i]['net_dividend'];
            if ($i > 0) {
                $data[$i]['received_dividend'] += $data[$i - 1]['received_dividend'];
            }
            $newShares = $data[$i]['net_dividend'] / $data[$i]['shareprice'];
            $data[$i]['new_amount'] = $newShares;

            $data[$i]['extra_dividend'] = $newShares * $netDividend;
            $oldShares += $newShares;
            $data[$i]['quator'] = ($startYear + $year);
            if ($payoutFrequency === 4) {
                $data[$i]['quator'] .= 'Q';
            }
            if ($payoutFrequency === 12) {
                $data[$i]['quator'] .= 'M';
            }
            $data[$i]['quator'] .= ($quator + 1);

            //$data[$i]['yoc'] = $data[$i]['net_dividend'] / ($oldShares )

            $quator++;
        }

        return $data;
    }
}
