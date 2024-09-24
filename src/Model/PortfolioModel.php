<?php

namespace App\Model;

use App\Entity\Pie;
use App\Entity\Ticker;
use App\Entity\Position;
use App\Entity\PortfolioItem;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Service\DividendService;
use DateTime;
use RuntimeException;
use Symfony\Component\Stopwatch\Stopwatch;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Doctrine\ORM\QueryAdapter;

class PortfolioModel
{
    public const CACHE_KEY = 'portfolio_model_cache_key';
    public const CACHE_NAMESPACE = 'portfolio.cache';
    private bool $initialized = false;
    private array $portfolioItems = [];
    private ?Pagerfanta $pagerfanta = null;

    public function __construct(private Stopwatch $stopwatch)
    {
    }

    /**
     * Add the dividend
     */
    private function getDividends(
        PaymentRepository $paymentRepository,
        array $tickerIds
    ): void {
        $this->stopwatch->start('portfoliomodel-getDividends');
        $dividends = $paymentRepository->getSumDividends($tickerIds);
        foreach ($this->portfolioItems as &$portfolioItem) {
            $tickerId = $portfolioItem->getTickerId();
            if (isset($dividends[$tickerId])) {
                $portfolioItem->setDividend($dividends[$tickerId]);
            }
        }
        $this->stopwatch->stop('portfoliomodel-getDividends');
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
        DividendService $dividendService
    ): array {
        $this->stopwatch->start('portfoliomodel-createPortfolioItem');

        $currentDate = new DateTime();

        // Need all Position ids and all Ticker ids
        $tickerIds = [];

        /**
         * @var Position $position
         */
        foreach ($positions as $position) {
            /**
             * @var Ticker $ticker
             */
            $ticker = $position->getTicker();
            $avgPrice = $position->getPrice();
            $allocation = $position->getAllocation();
            $percentageAllocation = ($allocation / $totalInvested) * 100;

            $payoutFrequency = $ticker->getPayoutFrequency();
            $portfolioItem = new PortfolioItem();
            $portfolioItem
                ->setTickerId($ticker->getId())
                ->setSymbol($ticker->getSymbol())
                ->setFullname($ticker->getFullname())
                ->setPositionId($position->getId())
                ->setPosition($position)
                ->setPrice($avgPrice)
                ->setAllocation($position->getAllocation())
                ->setAmount($position->getAmount())
                ->setPercentageAllocation($percentageAllocation)
                ->setPies($position->getPies())
                ->setDividendPayoutFrequency($payoutFrequency)
                ->setDividendTreshold(0.03);
            if ($position->getDividendTreshold()) {
                $portfolioItem->setDividendTreshold(
                    $position->getDividendTreshold() / 100
                );
            }

            if ($position->getMaxAllocation() !== null) {
                $portfolioItem->setMaxAllocation($position->getMaxAllocation());
                if (
                    $position->getAllocation() > $position->getMaxAllocation()
                ) {
                    $portfolioItem->setIsMaxAllocation(true);
                    $portfolioItem->setMaxAllocation(
                        $position->getMaxAllocation()
                    );
                }
            }

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

                $portfolioItem
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
                    $portfolioItem->setExDividendDate(
                        $calendar->getExDividendDate()
                    );
                }
                if ($calendar->getPaymentDate() instanceof DateTime) {
                    $portfolioItem->setPaymentDate($calendar->getPaymentDate());
                }

                foreach (
                    $position->getTicker()->getCalendars()
                    as $currentCalendar
                ) {
                    if ($currentCalendar->getPaymentDate() >= $currentDate) {
                        $portfolioItem->addDividendCalendar($currentCalendar);
                    }
                    if ($currentCalendar->getPaymentDate() < $currentDate) {
                        break;
                    }
                }
            }
            $this->portfolioItems[] = $portfolioItem;

            $id = $position->getTicker()->getId();
            if (isset($id) && !in_array($id, $tickerIds)) {
                $tickerIds[] = $id;
            }
        }
        $this->stopwatch->stop('portfoliomodel-createPortfolioItem');

        return $tickerIds;
    }

    public function getPager(
        PositionRepository $positionRepository,
        PaymentRepository $paymentRepository,
        DividendService $dividendService,
        float $totalInvested = 0.0,
        int $page = 1,
        string $orderBy = 'symbol',
        string $sort = 'asc',
        ?Ticker $ticker = null,
        ?Pie $pie = null
    ): static {
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

        if ($pie && $pie->getId()) {
            $query = $positionRepository->getAllQuery(
                $order,
                $sort,
                $ticker,
                PositionRepository::OPEN,
                $pie
            );
        } else {
            $query = $positionRepository->getAllQuery(
                $order,
                $sort,
                $ticker,
                PositionRepository::OPEN
            );
        }

        $adapter = new QueryAdapter($query);
        $this->pagerfanta = new Pagerfanta($adapter);
        $this->pagerfanta->setMaxPerPage(10);
        $this->pagerfanta->setCurrentPage($page);

        $tickerIds = $this->createPortfolioItem(
            $this->pagerfanta,
            $totalInvested,
            $dividendService
        );
        $this->getDividends($paymentRepository, $tickerIds);

        $this->initialized = true;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  array
     */
    public function getPortfolioItems(): array
    {
        if (!$this->initialized) {
            throw new RuntimeException('First call PortfolioModel::getPage()');
        }
        return $this->portfolioItems;
    }

    public function getPagerfanta(): Pagerfanta
    {
        if (!$this->initialized || !$this->pagerfanta instanceof Pagerfanta) {
            throw new RuntimeException('First call PortfolioModel::getPager()');
        }
        return $this->pagerfanta;
    }
}
