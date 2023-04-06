<?php

namespace App\Controller\Report;

use App\Repository\PositionRepository;
use App\Model\AllocationModel;
use App\Service\Summary;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/dashboard/report')]
class AllocationPerPositionController extends AbstractController
{
    public const TAX_DIVIDEND = 0.15; // %
    public const EXCHANGE_RATE = 1.19; // dollar to euro
    public const YIELD_PIE_KEY = 'yeildpie_searchPie';

    #[Route(path: '/allocation/position', name: 'report_allocation_position')]
    public function index(PositionRepository $positionRepository, Summary $summary, AllocationModel $allocation)
    {
        $result = $allocation->position($positionRepository, $summary);

        return $this->render('report/allocation/position.html.twig', array_merge($result, ['controller_name' => 'ReportController']));
    }
}
