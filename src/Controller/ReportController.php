<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\BranchRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\PositionRepository;

/**
 * @Route("/report")
 */
class ReportController extends AbstractController
{
    /**
     * @Route("/report", name="report_index")
     */
    public function index(
        BranchRepository $branchRepository,
        PositionRepository $positionRepository
    ): Response {
        $allocated = $positionRepository->getSumAllocated();
        $branches = $branchRepository->findAll();

        $data = [];
        foreach ($branches as $branch) {
            $data[$branch->getLabel()] = [
                'allocation' => 0,
                'allocationPercentage' => 0,
                'dividend' => 0,
                'tickers' => 0,
            ];
            $tickers = $branch->getTickers();
            foreach ($tickers as $tickers) {
                $data[$branch->getLabel()]['tickers'] += 1;
                foreach ($tickers->getPositions() as $position) {
                    $data[$branch->getLabel()]['allocation'] += $position->getAllocation();
                    $data[$branch->getLabel()]['dividend'] += $position->getDividend();
                }
            }
            $data[$branch->getLabel()]['allocationPercentage'] = ($data[$branch->getLabel()]['allocation'] / $allocated) * 100;
        }

        return $this->render('report/index.html.twig', [
            'data' => $data,
            'controller_name' => 'ReportController',
        ]);
    }
}
