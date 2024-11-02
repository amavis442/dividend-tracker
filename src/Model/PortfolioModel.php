<?php

namespace App\Model;

use App\Contracts\Service\DividendServiceInterface;
use App\Entity\Pie;
use App\Entity\Ticker;
use App\Entity\Position;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Service\DividendService;
use DateTime;
use Symfony\Component\Stopwatch\Stopwatch;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Doctrine\ORM\QueryAdapter;

class PortfolioModel
{
    public function __construct(
        private Stopwatch $stopwatch,
        private int $maxPerPage = 10
    ) {
    }

    /**
     * Page Decorator
     */
    private function createPortfolioItem(
        /**
         * @var \Traversable<Position> $positions
         */
        \Traversable $positions,
        float $totalInvested,
        DividendServiceInterface $dividendService
    ): void {
        $this->stopwatch->start('portfoliomodel-createPortfolioItem');

        $currentDate = new DateTime();

        /**
         * @var Position $position
         */
        foreach ($positions as $position) {
            /**
             * @var Ticker $ticker
             */
            $ticker = $position->getTicker();
            $payoutFrequency = $ticker->getPayoutFrequency();

            $position
                ->setDividendPayoutFrequency($payoutFrequency)
                ->setPercentageAllocation($totalInvested)
                ->computeIsMaxAllocation()
                ->computeCurrentDividendDates($currentDate)
                ->computeReceivedDividends();

            // Dividend part
            $calendar = $dividendService->getRegularCalendar(
                $position->getTicker()
            );

            if ($calendar) {
                $forwardNetDividend = $dividendService->getForwardNetDividend(
                    $position->getTicker(),
                    $position->getAmount()
                );
                $forwardNetDividendYield = $dividendService->getForwardNetDividendYield(
                    $position,
                    $position->getTicker(),
                    $position->getAmount(),
                    $position->getAllocation()
                );
                $forwardNetDividendYieldPerShare = 0;
                $netDividendPerShare = $dividendService->getNetDividendPerShare(
                    $position
                );

                $position
                    ->setDivDate(true)
                    ->setCashAmount($calendar->getCashAmount())
                    ->setCashCurrency($calendar->getCurrency())
                    ->setForwardNetDividend($forwardNetDividend)
                    ->setForwardNetDividendYield($forwardNetDividendYield)
                    ->setForwardNetDividendYieldPerShare(
                        $forwardNetDividendYieldPerShare
                    )
                    ->setNetDividendPerShare($netDividendPerShare);

                if ($calendar->getExDividendDate() instanceof DateTime) {
                    $position->setExDividendDate(
                        $calendar->getExDividendDate()
                    );
                }

                if ($calendar->getPaymentDate() instanceof DateTime) {
                    $position->setPaymentDate($calendar->getPaymentDate());
                }
            }
        }
        $this->stopwatch->stop('portfoliomodel-createPortfolioItem');
    }

    public function getPager(
        PositionRepository $positionRepository,
        DividendServiceInterface $dividendService,
        float $totalInvested = 0.0,
        int $page = 1,
        string $sort = 'symbol',
        string $orderBy = 'asc',
        ?Ticker $ticker = null,
        ?Pie $pie = null
    ): Pagerfanta {

        $sort = match($sort) {
            'industry' => 'i.label',
            'symbol' => 't.symbol',
            'fullname' => 't.fullname',
            default => 't.symbol'
        };

        $orderBy = in_array($orderBy, ['asc', 'desc', 'ASC', 'DESC']) ? $orderBy: 'asc';

        $queryBuilder = $positionRepository->getAllQuery(
            $sort,
            $orderBy,
            $ticker,
            PositionRepository::OPEN,
            $pie
        );

        $adapter = new QueryAdapter($queryBuilder);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($this->maxPerPage);
        $pagerfanta->setCurrentPage($page);

        $this->createPortfolioItem(
            $pagerfanta,
            $totalInvested,
            $dividendService
        );

        return $pagerfanta;
    }
}
