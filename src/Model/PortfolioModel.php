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
    public function __construct(private Stopwatch $stopwatch)
    {
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
                    $position
                );
                $forwardNetDividendYield = $dividendService->getForwardNetDividendYield(
                    $position
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
        string $orderBy = 'symbol',
        string $sort = 'asc',
        ?Ticker $ticker = null,
        ?Pie $pie = null
    ): Pagerfanta {
        $order = 't.symbol';
        if ($orderBy == 'industry') {
            $order = 'i.label';
        }
        if (in_array($orderBy, ['symbol', 'fullname'])) {
            $order = 't.' . $orderBy;
        }
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'asc';
        }

        $queryBuilder = $positionRepository->getAllQuery(
            $order,
            $sort,
            $ticker,
            PositionRepository::OPEN,
            $pie
        );

        $adapter = new QueryAdapter($queryBuilder);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(10);
        $pagerfanta->setCurrentPage($page);

        $this->createPortfolioItem(
            $pagerfanta,
            $totalInvested,
            $dividendService
        );

        return $pagerfanta;
    }
}
