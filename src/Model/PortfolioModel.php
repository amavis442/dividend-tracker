<?php

namespace App\Model;

use App\Entity\PortfolioItem;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Service\DividendService;
use DateTime;
use RuntimeException;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class PortfolioModel
{
    public const CACHE_KEY = 'portfolio_model_cache_key';
    public const CACHE_NAMESPACE = 'portfolio.cache';
    private bool $initialized = false;
    private array $portfolioItems = [];
    private array $tickerIds = [];
    private int $maxPages = 1;

    /**
     * When was the quote cache last updated
     */
    private int $timestamp = 0;

    public function __construct(
        private Stopwatch $stopwatch,
    ) {}

    private function getDividends(PaymentRepository $paymentRepository, array $tickerIds): void
    {
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
     * @var \Traversable<Position> $positions
     */
    private function createPortfolioItem(
        \Traversable $positions,
        float $totalInvested,
        DividendService $dividendService
    ): array {

        $this->stopwatch->start('portfoliomodel-createPortfolioItem');

        $currentDate = new DateTime();

        // Need all Position ids and all Ticker ids

        $tickerIds = [];

        /**
         * @var \App\Entity\Position $position
         */
        foreach ($positions as $position) {
            /**
             * @var \App\Entity\Ticker $ticker
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
                ->setDividendTreshold(0.03)
            ;
            if ($position->getDividendTreshold()) {
                $portfolioItem->setDividendTreshold($position->getDividendTreshold() / 100);
            }

            if ($position->getMaxAllocation() !== null) {
                $portfolioItem->setMaxAllocation($position->getMaxAllocation());
                if ($position->getAllocation() > $position->getMaxAllocation()) {
                    $portfolioItem->setIsMaxAllocation(true);
                    $portfolioItem->setMaxAllocation($position->getMaxAllocation());
                }
            }

            // Dividend part
            $calendar = $dividendService->getRegularCalendar($position->getTicker());
            if ($calendar) {
                $forwardNetDividend = $dividendService->getForwardNetDividend($position);
                $forwardNetDividendYield = $dividendService->getForwardNetDividendYield($position);
                $forwardNetDividendYieldPerShare = 0;
                $netDividendPerShare = $dividendService->getNetDividendPerShare($position);

                $portfolioItem
                    ->setDivDate(true)
                    ->setCashAmount($calendar->getCashAmount())
                    ->setCashCurrency($calendar->getCurrency())
                    ->setForwardNetDividend($forwardNetDividend)
                    ->setForwardNetDividendYield($forwardNetDividendYield)
                    ->setForwardNetDividendYieldPerShare($forwardNetDividendYieldPerShare)
                    ->setNetDividendPerShare($netDividendPerShare)
                ;
                if ($calendar->getExDividendDate() instanceof DateTime) {
                    $portfolioItem->setExDividendDate($calendar->getExDividendDate());
                }
                if ($calendar->getPaymentDate() instanceof DateTime) {
                    $portfolioItem->setPaymentDate($calendar->getPaymentDate());
                }

                foreach ($position->getTicker()->getCalendars() as $currentCalendar) {
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

    /**
     * Get 1 page of many with position data
     *
     * @param PositionRepository $positionRepository
     * @param DividendService $dividendService
     * @param PaymentRepository $paymentRepository
     * @param float $totalInvested
     * @param integer $page
     * @param string $orderBy
     * @param string $sort
     * @param string $searchCriteria
     * @param string|null $pieSelected
     * @return self
     */
    public function getPage(
        PositionRepository $positionRepository,
        DividendService $dividendService,
        PaymentRepository $paymentRepository,
        float $totalInvested = 0.0,
        int $page = 1,
        string $orderBy = 'symbol',
        string $sort = 'asc',
        string $searchCriteria = '',
        ?string $pieSelected = null
    ): self {
        $order = 't.symbol';
        if (in_array($orderBy, ['industry'])) {
            $order = 'i.label';
        }
        if (in_array($orderBy, ['symbol', 'fullname'])) {
            $order = 't.' . $orderBy;
        }
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'asc';
        }

        $this->stopwatch->start('portfoliomodel-getpage');
        $limit = 20;
        if ($pieSelected && $pieSelected != '-') {
            $items = $positionRepository->getAll($page, $limit, $order, $sort, $searchCriteria, PositionRepository::OPEN, [$pieSelected]);
        } else {
            $items = $positionRepository->getAll($page, $limit, $order, $sort, $searchCriteria, PositionRepository::OPEN);
        }

        $this->maxPages = (int) ceil($items->count() / $limit);
        $iter = $items->getIterator();
        $tickerIds = [];
        $this->stopwatch->stop('portfoliomodel-getpage');

        $tickerIds = $this->createPortfolioItem($iter, $totalInvested, $dividendService, $tickerIds);
        $this->getDividends($paymentRepository, $tickerIds);

        $this->tickerIds = $tickerIds;
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

    /**
     * Get unique ticker ids
     *
     * @return  array
     */
    public function getTickerIds(): array
    {
        if (!$this->initialized) {
            throw new RuntimeException('First call PortfolioModel::getPage()');
        }
        return $this->tickerIds;
    }

    /**
     * Get num Pages
     *
     * @return  integer
     */
    public function getMaxPages(): int
    {
        if (!$this->initialized) {
            throw new RuntimeException('First call PortfolioModel::getPage()');
        }
        return $this->maxPages;
    }

    /**
     * Get when was the quote cache last updated
     *
     * @return  int
     */
    public function getCacheTimestamp(): int
    {
        if (!$this->initialized) {
            throw new RuntimeException('First call PortfolioModel::getPage()');
        }
        return $this->timestamp;
    }

    public static function clearCache(): void
    {
        // Clear the portfolioModel cache, when we have new data.
        $cache = new FilesystemAdapter(self::CACHE_NAMESPACE);

        $cache->clear();
    }
}
