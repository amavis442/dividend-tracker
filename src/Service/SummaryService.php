<?php

namespace App\Service;

use App\Entity\Summary;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Contracts\Service\SummaryInterface;

class SummaryService implements SummaryInterface
{
    protected PositionRepository $positionRepository;
    protected PaymentRepository $paymentRepository;

    public function __construct(
        PositionRepository $positionRepository,
        PaymentRepository $paymentRepository
    ) {
        $this->positionRepository = $positionRepository;
        $this->paymentRepository = $paymentRepository;
    }

    public function getSummary(): Summary
    {
        $positions = $this->positionRepository->getOpenPositions();
        $numActivePosition = count($positions);
        $numTickers = $numActivePosition;
        $profit = 0.0;
        $allocated = 0.0;
        /**
         * @var  \App\Entity\Position  $position
         */
        foreach ($positions as $position) {
            $profit += $position->getProfit();
            $allocated += $position->getAllocation();
        }
        //$allocated = $this->getTotalAllocated();
        $totalDividend = $this->paymentRepository->getTotalDividend();

        $summary = new Summary();
        $summary
            ->setNumActivePosition($numActivePosition)
            ->setNumTickers($numTickers)
            ->setProfit($profit)
            ->setTotalDividend($totalDividend)
            ->setAllocated($allocated);

        return $summary;
    }

    public function getTotalAllocated(): float
    {
        return $this->positionRepository->getSumAllocated();
    }
}
