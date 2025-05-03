<?php

namespace App\Controller;

use App\Entity\IncomesShare;
use App\Entity\IncomesShares;
use App\Entity\IncomesSharesData;
use App\Form\IncomesSharesType;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/incomesshares')]
final class IncomesSharesController extends AbstractController
{
	#[Route('/', name: 'app_incomes_shares')]
	public function index(
		Request $request,
		TickerRepository $tickerRepository,
		PositionRepository $positionRepository,
		PaymentRepository $paymentRepository,
        EntityManagerInterface $em,
	): Response {
		$incomesShares = new IncomesShares();
		$ticker = [];

		$share = new IncomesShare();
		$ticker['XS2901886445'] = $tickerRepository->findOneBy([
			'isin' => 'XS2901886445',
		]);
		$share->setIsin($ticker['XS2901886445']->getIsin());
		$share->setFullname($ticker['XS2901886445']->getFullname());
		$incomesShares->getShares()->add($share);

		$share2 = new IncomesShare();
		$ticker['XS2875105608'] = $tickerRepository->findOneBy([
			'isin' => 'XS2875105608',
		]);
		$share2->setIsin($ticker['XS2875105608']->getIsin());
		$share2->setFullname($ticker['XS2875105608']->getFullname());
		$incomesShares->getShares()->add($share2);

		$share3 = new IncomesShare();
		$ticker['XS2852999692'] = $tickerRepository->findOneBy([
			'isin' => 'XS2852999692',
		]);
		$share3->setIsin($ticker['XS2852999692']->getIsin());
		$share3->setFullname($ticker['XS2852999692']->getFullname());
		$incomesShares->getShares()->add($share3);

		$share4 = new IncomesShare();
		$ticker['XS2875106242'] = $tickerRepository->findOneBy([
			'isin' => 'XS2875106242',
		]);
		$share4->setIsin($ticker['XS2875106242']->getIsin());
		$share4->setFullname($ticker['XS2875106242']->getFullname());
		$incomesShares->getShares()->add($share4);

		$share5 = new IncomesShare();
		$ticker['XS2852999429'] = $tickerRepository->findOneBy([
			'isin' => 'XS2852999429',
		]);
		$share5->setIsin($ticker['XS2852999429']->getIsin());
		$share5->setFullname($ticker['XS2852999429']->getFullname());
		$incomesShares->getShares()->add($share5);

		$form = $this->createForm(IncomesSharesType::class, $incomesShares);

		$form->handleRequest($request);

		$data = [];
        $totalDistributions = 0.0;
        $totalAllocation = 0.0;
        $totalProfitLoss = 0.0;
        $yield = 0.0;
		if ($form->isSubmitted() && $form->isValid()) {
			// ... do your form processing, like saving the Task and Tag entities
			$shares = $incomesShares->getShares();
            $saveData = false;
            if ($form->get('save')->isClicked()) {
                $saveData = true;
            }

			foreach ($shares as $ishare) {
				$tick = $ticker[$ishare->getIsin()];

				$position = $positionRepository->findOneBy([
					'ticker' => $tick->getId(),
					'closed' => false,
				]);
				$allocation = $position->getAllocation();
				$distributions = $paymentRepository->getSumDividendsByPosition(
					$position
				);

				$totalReturn =
					$allocation + $distributions + $ishare->getProfitLoss();
                $totalGain = ($totalReturn-$allocation);
				$totalReturnPercentage = ($totalGain / $allocation) * 100;

                $price = $ishare->getPrice();
                $amount = $position->getAmount();

                $calcGain = $price * $amount - $allocation;

                $totalDistributions += $distributions;
                $totalAllocation += $allocation;

                $totalProfitLoss += $ishare->getProfitLoss();

				$data[$ishare->getIsin()] = [
                    'fullname' => $tick->getFullname(),
					'allocation' => $allocation,
                    'amount' => $amount,
                    'price' => $price,
                    'calcGain' => $calcGain,
					'distributions' => $distributions,
                    'pl' => $ishare->getProfitLoss(),
                    'totalGain' => $totalGain,
					'totalReturn' => $totalReturn,
					'totalReturnPercentage' => $totalReturnPercentage,
				];


                if ($saveData) {
                    $incomesSharesData = new IncomesSharesData();
                    $incomesSharesData->setTicker($tick);
                    $incomesSharesData->setPosition($position);
                    $incomesSharesData->setPrice($price);
                    $incomesSharesData->setProfitLoss($ishare->getProfitLoss());
                    $incomesSharesData->setDistributions($distributions);
                    $incomesSharesData->setAllocation($allocation);
                    $incomesSharesData->setAmount($amount);
                    $incomesSharesData->setCreatedAt(new \DateTimeImmutable());
                    $incomesSharesData->setUpdatedAt(new \DateTimeImmutable());

                    $em->persist($incomesSharesData);
                }
			}
            if ($saveData) {
                $em->flush();
            }

            $yield = 0.0;
            if ($totalAllocation > 0 ) {
                $yield = ($totalDistributions / $totalAllocation) * 100;
            }




		}


        return $this->render('incomes_shares/index.html.twig', [
			'controller_name' => 'IncomesSharesController',
			'form' => $form,
            'data' => $data,
            'totalProfitLoss' => $totalProfitLoss,
            'totalDistribution' => $totalDistributions,
            'totalAllocation' => $totalAllocation,
            'yield' => $yield,
		]);
	}
}
