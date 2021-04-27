<?php

namespace App\Model;

use App\Entity\Position;
use App\Entity\PortfolioItem;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Service\DividendService;
use App\Service\StockPriceService;
use App\Service\YahooFinanceService;
use RuntimeException;
use DateTime;

class PortfolioModel
{
    /**
     * Undocumented variable
     *
     * @var boolean
     */
    private $initialized = false;

    /**
     * Undocumented variable
     *
     * @var PositionItems[]
     */
    private $portfolioItems = [];
    /**
     * Unique ticker ids
     *
     * @var array
     */
    private $tickerIds = [];
    /**
     * num Pages
     *
     * @var integer
     */
    private $maxPages = 1;

    /**
     * When was the quote cache last updated
     *
     * @var int
     */
    private $timestamp;

    /**
     * Get 1 page of many with position data
     *
     * @param PositionRepository $positionRepository
     * @param TickerRepository $tickerRepository
     * @param DividendService $dividendService
     * @param PaymentRepository $paymentRepository
     * @param YahooFinanceService $yahooFinanceService
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
        TickerRepository $tickerRepository,
        DividendService $dividendService,
        PaymentRepository $paymentRepository,
        StockPriceService $stockPriceService,
        string $cacheTag = 'all',
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

        $limit = 20;
        if ($pieSelected && $pieSelected != '-') {
            $items = $positionRepository->getAll($page, $limit, $order, $sort, $searchCriteria, PositionRepository::OPEN, [$pieSelected]);
        } else {
            $items = $positionRepository->getAll($page, $limit, $order, $sort, $searchCriteria, PositionRepository::OPEN);
        }

        $this->maxPages = (int) ceil($items->count() / $limit);
        $iter = $items->getIterator();
        $tickerIds = [];
        $symbols = [];

        $tickers = $tickerRepository->getActive();
        foreach ($tickers as $ticker)
        {
            $symbol = $ticker->getSymbol();
            $symbols[] = $symbol;
        }
        
        $stockPriceService->getQuotes($symbols);
        $this->timestamp = $stockPriceService->getCacheTimeStamp();

        /**
         * @var Position $position
         */
        foreach ($iter as $position) {
            $ticker = $position->getTicker();
            $symbol = $ticker->getSymbol();
 
            $paperProfit = 0.0;
            $paperProfitPercentage = 0.0;
            $diffPrice = 0.0;
            
            $marketPrice = $stockPriceService->getMarketPrice($symbol);
            $amount = $position->getAmount();
            $avgPrice = $position->getPrice();
            $allocation = $position->getAllocation();
            $percentageAllocation = ($allocation / $totalInvested) * 100;
            
            if ($marketPrice) {
                $paperProfit = ($marketPrice - $avgPrice) * $amount;
                $paperProfitPercentage = ($paperProfit / $allocation) * 100;
                $diffPrice = $marketPrice - $avgPrice;
            }

            $portfolioItem = new PortfolioItem();
            $portfolioItem
                ->setTickerId($ticker->getId())
                ->setSymbol($ticker->getSymbol())
                ->setFullname($ticker->getFullname())
                ->setPositionId($position->getId())
                ->setPosition($position)
                ->setPrice($avgPrice)
                ->setMarketPrice($marketPrice)
                ->setAllocation($position->getAllocation())
                ->setAmount($position->getAmount())
                ->setPaperProfit($paperProfit)
                ->setPaperProfitPercentage($paperProfitPercentage)
                ->setPercentageAllocation($percentageAllocation)
                ->setPies($position->getPies())
                ->setIsDividendMonth($position->isDividendPayMonth())
                ->setDiffPrice($diffPrice)
                ->setDividendPayoutFrequency($ticker->getPayoutFrequency());
            ;

            // Dividend part
            $calendar = $position->getTicker()->getCalendars()->first();
            if ($calendar) {
                $forwardNetDividend = $dividendService->getForwardNetDividend($position);
                $forwardNetDividendYield = $dividendService->getForwardNetDividendYield($position);

                $portfolioItem
                    ->setDivDate(true)
                    ->setExDividendDate($calendar->getExDividendDate())
                    ->setPaymentDate($calendar->getPaymentDate())
                    ->setCashAmount($calendar->getCashAmount())
                    ->setCashCurrency($calendar->getCurrency())
                    ->setForwardNetDividend($forwardNetDividend)
                    ->setForwardNetDividendYield($forwardNetDividendYield);


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

        $dividends = $paymentRepository->getSumDividends($tickerIds);
        foreach ($this->portfolioItems as &$portfolioItem) {
            $tickerId = $portfolioItem->getTickerId();
            if (isset($dividends[$tickerId])) {
                $portfolioItem->setDividend($dividends[$tickerId]);
            }
        }
        $this->tickerIds = $tickerIds;
        $this->initialized = true;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  PositionItems[]
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
