<?php

namespace App\Controller\Report;

use App\Repository\PositionRepository;
use App\Model\AllocationModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/dashboard/report')]
class AllocationController extends AbstractController
{
    public const TAX_DIVIDEND = 0.15; // %
    public const EXCHANGE_RATE = 1.19; // dollar to euro
    public const YIELD_PIE_KEY = 'yeildpie_searchPie';

    #[Route(path: '/allocation/sector', name: 'report_allocation_sector')]
    public function index(
        PositionRepository $positionRepository,
        AllocationModel $allocation,
        TranslatorInterface $translator
    ) {
        $result = $allocation->allocation($positionRepository, $translator);

        return $this->render('report/allocation/index.html.twig', array_merge($result, ['controller_name' => 'ReportController']));
    }
}
