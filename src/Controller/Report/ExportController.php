<?php

namespace App\Controller\Report;

use App\Repository\PositionRepository;
use App\Service\Export;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/dashboard/report")
 */
class ExportController extends AbstractController
{
    public const TAX_DIVIDEND = 0.15; // %
    public const EXCHANGE_RATE = 1.19; // dollar to euro
    public const YIELD_PIE_KEY = 'yeildpie_searchPie';

    /**
     * @Route("/export", name="report_export")
     */
    public function index(PositionRepository $positionRepository): Response
    {
        $export = new Export($positionRepository);
        $filename = $export->export($positionRepository);

        $response = new BinaryFileResponse($filename);
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            date('Ymd') . '-export.xlsx'
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
