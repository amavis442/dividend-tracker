<?php

namespace App\Model;

use App\Entity\PortfolioItem;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Service\DividendService;
use App\Service\YahooFinanceService;
use RuntimeException;

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
     * Get 1 page of many with position data
     *
     * @param PositionRepository $positionRepository
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
        DividendService $dividendService,
        PaymentRepository $paymentRepository,
        YahooFinanceService $yahooFinanceService,
        float $totalInvested,
        int $page = 1,
        string $orderBy = 'ticker',
        string $sort = 'asc',
        string $searchCriteria = '',
        ?string $pieSelected = null
    ): self {

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

        foreach ($iter as $position) {
            $marketPrice = $yahooFinanceService->getQuote($position->getTicker()->getSymbol());
            $amount = $position->getAmount();
            $avgPrice = $position->getPrice();
            $allocation = $position->getAllocation();
            $percentageAllocation = ($allocation / $totalInvested) * 100;
            $paperProfit = ($marketPrice - $avgPrice) * $amount;
            $paperProfitPercentage = ($paperProfit / $allocation) * 100;
            $ticker = $position->getTicker();

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
}
