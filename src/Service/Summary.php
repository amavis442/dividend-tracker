<?php

namespace App\Service;

use App\Repository\PositionRepository;
use App\Repository\PaymentRepository;

class Summary
{
    protected PositionRepository $positionRepository;
    protected PaymentRepository $paymentRepository;

    public function __construct(PositionRepository $positionRepository, PaymentRepository $paymentRepository)
    {
        $this->positionRepository = $positionRepository;
        $this->paymentRepository = $paymentRepository;
    }

    public function getSummary(): array
    {
        /**
         * @var Array $positions
         */
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
            //$allocated += $position->getAllocation();
        }
        $allocated = $this->getTotalAllocated();
        $totalDividend = $this->paymentRepository->getTotalDividend();

        return [
            $numActivePosition,
            $numTickers,
            $profit,
            $totalDividend,
            $allocated
        ];
    }

    public function getTotalAllocated(): float
    {
        return $this->positionRepository->getSumAllocated();
    }
}
