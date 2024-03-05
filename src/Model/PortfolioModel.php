<?php

namespace App\Model;

use App\Entity\PortfolioItem;
use App\Entity\Position;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Service\DividendService;
use DateTime;
use RuntimeException;
use Symfony\Component\Stopwatch\Stopwatch;

class PortfolioModel
{
    /**
     * Undocumented variable
     *
     * @var boolean
     */
    private bool $initialized = false;

    /**
     * Undocumented variable
     *
     * @var array
     */
    private array $portfolioItems = [];
    /**
     * Unique ticker ids
     *
     * @var array
     */
    private array $tickerIds = [];
    /**
     * num Pages
     *
     * @var integer
     */
    private int $maxPages = 1;

    /**
     * When was the quote cache last updated
     *
     * @var int
     */
    private int $timestamp = 0;

    public function __construct(
        private Stopwatch $stopwatch,
    ) {
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
        float $totalInvested,
        int $page = 1,
        string $orderBy = 'ticker',
        string $sort = 'asc',
        string $searchCriteria = '',
        ?string $pieSelected = null
    ): self {
        $currentDate = new DateTime();

        $order = 't.ticker';
        if (in_array($orderBy, ['industry'])) {
            $order = 'i.label';
        }
        if (in_array($orderBy, ['ticker', 'fullname'])) {
            $order = 't.' . $orderBy;
        }
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'asc';
        }

        $this->stopwatch->start('portfoliomodel-getpage-get-iter');
        $limit = 20;
        if ($pieSelected && $pieSelected != '-') {
            $items = $positionRepository->getAll($page, $limit, $order, $sort, $searchCriteria, PositionRepository::OPEN, [$pieSelected]);
        } else {
            $items = $positionRepository->getAll($page, $limit, $order, $sort, $searchCriteria, PositionRepository::OPEN);
        }

        $this->maxPages = (int) ceil($items->count() / $limit);
        $iter = $items->getIterator();
        $tickerIds = [];
        $this->stopwatch->stop('portfoliomodel-getpage-get-iter');
        /**
         * @var Position $position
         */
        $this->stopwatch->start('portfoliomodel-getpage-main-foreach');
        foreach ($iter as $position) {
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
                ->setIsDividendMonth($position->isDividendPayMonth())
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
                    ->setExDividendDate($calendar->getExDividendDate())
                    ->setPaymentDate($calendar->getPaymentDate())
                    ->setCashAmount($calendar->getCashAmount())
                    ->setCashCurrency($calendar->getCurrency())
                    ->setForwardNetDividend($forwardNetDividend)
                    ->setForwardNetDividendYield($forwardNetDividendYield)
                    ->setForwardNetDividendYieldPerShare($forwardNetDividendYieldPerShare)
                    ->setNetDividendPerShare($netDividendPerShare)
                ;

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

            if (!in_array($id, $tickerIds)) {
                $tickerIds[] = $position->getTicker()->getId();
            }
        }
        $this->stopwatch->stop('portfoliomodel-getpage-main-foreach');

        $this->stopwatch->start('portfoliomodel-getpage-dividend-foreach');
        $dividends = $paymentRepository->getSumDividends($tickerIds);
        foreach ($this->portfolioItems as &$portfolioItem) {
            $tickerId = $portfolioItem->getTickerId();
            if (isset($dividends[$tickerId])) {
                $portfolioItem->setDividend($dividends[$tickerId]);
            }
        }
        $this->stopwatch->stop('portfoliomodel-getpage-dividend-foreach');

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
    public function getCacheTimestamp()
    {
        if (!$this->initialized) {
            throw new RuntimeException('First call PortfolioModel::getPage()');
        }
        return $this->timestamp;
    }
}
