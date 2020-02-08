<?php
namespace App\Service;

use App\Repository\PositionRepository;
use App\Repository\PaymentRepository;

class Summary
{
    public function __construct( PositionRepository $positionRepository, PaymentRepository $paymentRepository)
    {
        $this->positionRepository = $positionRepository;
        $this->paymentRepository = $paymentRepository;
    }

    public function getSummary():array
    {
        $numActivePosition = $this->positionRepository->getTotalPositions();
        $numTickers = $this->positionRepository->getTotalTickers();
        $profit = $this->positionRepository->getProfit();
        $totalDividend = $this->paymentRepository->getTotalDividend();
        $allocated = $this->positionRepository->getSumAllocated();
    
        return [
            $numActivePosition,
            $numTickers,
            $profit,
            $totalDividend,
            $allocated
        ];
    }

    public function getTotalAllocated(): int
    {
        return $this->positionRepository->getSumAllocated(); 
    }
}