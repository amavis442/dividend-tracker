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
        $form = $this->createForm(CompoundType::class, $compound);
        $form->handleRequest($request);

        $data = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $startDividend = $compound->getDividend() / 1000;
            $startAmount = $compound->getAmount();
            $startPrice = $compound->getPrice() / 1000;
            $dividendGrowthRate = $compound->getGrowth() / 1000;

            $oldShares = $startAmount / 10000000;
            $dividend = (float) $startDividend;
            $year = 0;
            $quator = 0;
            $startYear = date('Y');
            if (date('m') > 9) {
                $startYear++;
            }
            for ($i = 0; $i < 160; $i++) {
                $data[$i]['quator'] = '';
                $data[$i]['amount'] = $oldShares;
                $data[$i]['dividend'] = 0.0;
                $data[$i]['net_dividend'] = 0.0;
                $data[$i]['new_amount'] = 0.0;
                $data[$i]['extra_dividend'] = 0.0;

                if ($quator > 3) {
                    $year++;
                    $quator = 0;
                }
                if ($year > 0) {
                    $dividend = (float) ($startDividend) * pow(1 + ($dividendGrowthRate / 100), $year);
                }
                $data[$i]['dividend'] = $dividend;

                $netDividend = (($dividend * 0.85) / 1.19);
                
                $data[$i]['net_dividend'] = $oldShares * $netDividend;
                $newShares = floor($data[$i]['net_dividend'] / $startPrice);
                $data[$i]['new_amount'] = $newShares;

                $data[$i]['extra_dividend'] = $newShares * $netDividend;
                $oldShares += $newShares;
                $data[$i]['quator'] = ($startYear + $year) . 'Q' . ($quator + 1);
                $quator++;
            }
        }

        return $this->render('compound_prediction/index.html.twig', [
            'controller_name' => 'CompoundPredictionController',
            'form' => $form->createView(),
            'data' => $data,
        ]);
    }
}
