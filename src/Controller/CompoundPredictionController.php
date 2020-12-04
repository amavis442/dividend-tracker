<?php

namespace App\Controller;

use App\Entity\Compound;
use App\Form\CompoundType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/dashboard/compound")
 */
class CompoundPredictionController extends AbstractController
{
    /**
     * @Route("/prediction", name="compound_prediction")
     */
    public function prediction(Request $request): Response
    {
        $compound = new Compound();
        $payoutFrequency = 4;
        $compound->setFrequency($payoutFrequency);
        $form = $this->createForm(CompoundType::class, $compound);
        $form->handleRequest($request);


        $data = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $startDividend = $compound->getDividend() / 1000;
            $startAmount = $compound->getAmount();
            $startPrice = $compound->getPrice() / 1000;
            $priceAppreciation = $compound->getPriceAppreciation() / 1000;
            $dividendGrowthRate = $compound->getGrowth() / 1000;
            $dividendGrowthRateAfter5Years = $compound->getGrowthAfter5Years() / 1000;
            $maxPrice = $compound->getMaxPrice() / 1000;
            $payoutFrequency = $compound->getFrequency();

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
            $reportRange = 40 * $payoutFrequency; // 40 Years

            for ($i = 0; $i < $reportRange; $i++) {
                $data[$i]['quator'] = '';
                $data[$i]['amount'] = $oldShares;
                $data[$i]['dividend'] = 0.0;
                $data[$i]['net_dividend'] = 0.0;
                $data[$i]['new_amount'] = 0.0;
                $data[$i]['extra_dividend'] = 0.0;
                $data[$i]['shareprice'] = $startPrice;
                $data[$i]['yoc'] = 0.0;

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

                $netDividend = (($dividend * 0.85) / 1.19);

                $data[$i]['net_dividend'] = $oldShares * $netDividend;
                $newShares = floor($data[$i]['net_dividend'] / $data[$i]['shareprice']);
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

                //$data[$i]['yoc'] = $data[$i]['net_dividend'] / ($oldShares *)
                $quator++;
            }
        }

        return $this->render('compound_prediction/index.html.twig', [
            'controller_name' => 'CompoundPredictionController',
            'form' => $form->createView(),
            'data' => $data,
            'payoutFrequency' => $payoutFrequency,
        ]);
    }
}
