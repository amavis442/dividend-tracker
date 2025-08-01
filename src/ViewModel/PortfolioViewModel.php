<?php

namespace App\ViewModel;use App\Entity\Pie;
use App\Entity\Ticker;
use App\Entity\Position;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Service\DividendServiceInterface;
use DateTime;
use Symfony\Component\Stopwatch\Stopwatch;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use App\Decorator\Factory\AdjustedPositionDecoratorFactory;
//use App\Service\MetricsUpdateService;

class PortfolioViewModel
{
    public function __construct(
        private Stopwatch $stopwatch,
        //private MetricsUpdateService $metricsUpdate,
        private DividendServiceInterface $dividendService,
        private PositionRepository $positionRepository,
        private AdjustedPositionDecoratorFactory $adjustedFactory,
        private int $maxPerPage = 10
    ) {
    }

    /**
     * Page Decorator
     */
    public function createPortfolioItem(
        /**
         * @var \Traversable<Position> $positions
         */
        \Traversable $positions,
        float $totalInvested,
    ): void {
        $this->stopwatch->start('portfoliomodel-createPortfolioItem');

        $currentDate = new DateTime();

        /**
         * @var Position $position
         */
        foreach ($positions as $position) {
            $decorator = $this->adjustedFactory->decorate($position);
            $amount = $decorator->getAdjustedAmount();
            $note = $decorator->getAdjustmentNote();

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
            $calendar = $this->dividendService->getRegularCalendar(
                $position->getTicker()
            );

            if ($calendar) {
                $forwardNetDividend = $this->dividendService->getForwardNetDividend(
                    $position->getTicker(),
                    $position->getAmount()
                );
                $forwardNetDividendYield = $this->dividendService->getForwardNetDividendYield(
                    $position,
                    $position->getTicker(),
                    $position->getAmount(),
                    $position->getAllocation()
                );
                $forwardNetDividendYieldPerShare = 0;
                $netDividendPerShare = $this->dividendService->getNetDividendPerShare(
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

        $queryBuilder = $this->positionRepository->getAllQuery(
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
            $totalInvested
        );

        return $pagerfanta;
    }
}
