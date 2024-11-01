<?php

namespace App\Controller;

use App\Entity\Compound;
use App\Form\CompoundType;
use App\Model\CompoundCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/compound')]
class CompoundPredictionController extends AbstractController
{
	#[Route(path: '/prediction', name: 'compound_prediction')]
	public function prediction(
		Request $request,
		CompoundCalculator $compoundCalculator
	): Response {
		$payoutFrequency = 4;
		$startCapital = 0.0;
		$compound = new Compound();
		$compound->setFrequency($payoutFrequency);

		$form = $this->createForm(CompoundType::class, $compound);
		$form->handleRequest($request);


		if ($form->isSubmitted() && $form->isValid()) {
			$data = $compoundCalculator->run($compound);

			return $this->render(
				'compound_prediction/_table-results.html.twig',
				[
					'data' => $data,
					'startCapital' => $startCapital,
					'payoutFrequency' => $payoutFrequency,
				]
			);
		}

        return $this->render('compound_prediction/index.html.twig', [
			'controller_name' => 'CompoundPredictionController',
			'form' => $form,
		]);
	}
}
