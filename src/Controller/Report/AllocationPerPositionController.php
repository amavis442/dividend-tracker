<?php

namespace App\Controller\Report;

use App\Repository\BranchRepository;
use App\Repository\PaymentRepository;
use App\Repository\PieRepository;
use App\Repository\PositionRepository;
use App\Service\Allocation;
use App\Service\Export;
use App\Service\Payouts;
use App\Service\Summary;
use App\Service\Yields;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/dashboard/report")
 */
class AllocationPerPositionController extends AbstractController
{
    public const TAX_DIVIDEND = 0.15; // %
    public const EXCHANGE_RATE = 1.19; // dollar to euro
    public const YIELD_PIE_KEY = 'yeildpie_searchPie';

    /**
     * @Route("/allocation/position", name="report_allocation_position")
     */
    public function index(PositionRepository $positionRepository, Summary $summary, Allocation $allocation)
    {
        $result = $allocation->position($positionRepository, $summary);

        return $this->render('report/allocation/position.html.twig', array_merge($result, ['controller_name' => 'ReportController']));
    }
}
