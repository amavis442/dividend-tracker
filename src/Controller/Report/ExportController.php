<?php

declare(strict_types=1);

namespace App\Controller\Report;

use App\Service\Export;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/report')]
class ExportController extends AbstractController
{
    public const YIELD_PIE_KEY = 'yeildpie_searchPie';

    #[Route(path: '/export', name: 'report_export')]
    public function index(Export $export): Response
    {
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
