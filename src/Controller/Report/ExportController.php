<?php

namespace App\Controller\Report;

use App\Repository\PieRepository;
use App\Repository\PositionRepository;
use App\Service\DividendServiceInterface;
use App\Service\Export;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\DividendExchangeRateResolverInterface;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/report')]
class ExportController extends AbstractController
{
	public const TAX_DIVIDEND = 0.15; // %
	public const EXCHANGE_RATE = 1.19; // dollar to euro
	public const YIELD_PIE_KEY = 'yeildpie_searchPie';

	#[Route(path: '/export', name: 'report_export')]
	public function index(
		PositionRepository $positionRepository,
		DividendServiceInterface $dividendService,
		DividendExchangeRateResolverInterface $dividendExchangeRateResolver,
		PieRepository $pieRepository
	): Response {
		$export = new Export(
			$positionRepository,
			$dividendService,
			$dividendExchangeRateResolver,
			$pieRepository
		);
		$filename = $export->export();

		$response = new BinaryFileResponse($filename);
		$disposition = HeaderUtils::makeDisposition(
			HeaderUtils::DISPOSITION_ATTACHMENT,
			date('Ymd') . '-export.xlsx'
		);
		$response->headers->set('Content-Disposition', $disposition);

		return $response;
	}
}
