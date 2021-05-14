<?php

namespace App\Entity;

use App\Entity\Currency;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class PortfolioItem
{
    /**
     * Undocumented variable
     *
     * @var \App\Entity\Position
     */
    private $position;
    /**
     * position avg price
     *
     * @var float
     */
    private $price;

    /**
     * Market price
     *
     * @var float
     */
    private $marketPrice;

    /**
     * Undocumented variable
     *
     * @var float
     */
    private $paperProfit;

    /**
     * Undocumented variable
     *
     * @var float
     */
    private $paperProfitPercentage;

    /**
     * Undocumented variable
     *
     * @var DateTime
     */
    private $exDividendDate;

    /**
     * Undocumented variable
     *
     * @var DateTime
     */
    private $paymentDate;

    /**
     * Undocumented variable
     *
     * @var float
     */
    private $cashAmount;

    /**
     * Undocumented variable
     *
     * @var Currency
     */
    private $cashCurrency;

    /**
     * Undocumented variable
     *
     * @var float
     */
    private $percentageAllocation;

    /**
     * Undocumented variable
     *
     * @var float
     */
    private $forwardNetDividend;

    /**
     * Undocumented variable
     *
     * @var float
     */
    private $forwardNetDividendYield;

    /**
     * Current dividend yield per share based on current marketprice
     *
     * @var float
     */
    private $forwardNetDividendYieldPerShare;

    /**
     * Undocumented variable
     *
     * @var string
     */
    private $symbol;

    /**
     * Undocumented variable
     *
     * @var string
     */
    private $fullname;

    /**
     * Pies
     *
     * @var Collection
     */
    private $pies;

    /**
     * Has a calendar entry
     *
     * @var bool
     */
    private $divDate = false;

    /**
     * Position allocation
     *
     * @var float
     */
    private $allocation;

    /**
     * How many shares
     *
     * @var float
     */
    private $amount;

    /**
     * Total received dividends
     *
     * @var float
     */
    private $dividend;

    /**
     * Ticker id
     *
     * @var int
     */
    private $tickerId;

    /**
     * Position id
     *
     * @var int
     */
    private $positionId;

    /**
     * Current dividend month?
     *
     * @var boolean
     */
    private $isDividendMonth = false;
    /**
     * Diffrence between avg price and market price
     *
     * @var float
     */
    private $diffPrice;

    /**
     * How times per year will there be a dividend payout
     *
     * @var int
     */
    private $dividendPayoutFrequency;

    /**
     * Collection of dividend calenders of future payments
     *
     * @var Collection
     */
    private $dividendCalendars;

    public function __construct()
    {
        $this->dividendCalendars = new ArrayCollection();

    }

    /**
     * Get undocumented variable
     *
     * @return  \App\Entity\Position
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set undocumented variable
     *
     * @param  \App\Entity\Position  $position  Undocumented variable
     *
     * @return  self
     */
    public function setPosition(\App\Entity\Position $position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set undocumented variable
     *
     * @param  float  $price  Undocumented variable
     *
     * @return  self
     */
    public function setPrice(float $price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  float
     */
    public function getPaperProfit()
    {
        return $this->paperProfit;
    }

    /**
     * Set undocumented variable
     *
     * @param  float  $paperProfit  Undocumented variable
     *
     * @return  self
     */
    public function setPaperProfit(float $paperProfit)
    {
        $this->paperProfit = $paperProfit;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  float
     */
    public function getPaperProfitPercentage()
    {
        return $this->paperProfitPercentage;
    }

    /**
     * Set undocumented variable
     *
     * @param  float  $paperProfitPercentage  Undocumented variable
     *
     * @return  self
     */
    public function setPaperProfitPercentage(float $paperProfitPercentage)
    {
        $this->paperProfitPercentage = $paperProfitPercentage;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  DateTime
     */
    public function getExDividendDate()
    {
        return $this->exDividendDate;
    }

    /**
     * Set undocumented variable
     *
     * @param  DateTime  $exDividendDate  Undocumented variable
     *
     * @return  self
     */
    public function setExDividendDate(DateTime $exDividendDate)
    {
        $this->exDividendDate = $exDividendDate;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  DateTime
     */
    public function getPaymentDate()
    {
        return $this->paymentDate;
    }

    /**
     * Set undocumented variable
     *
     * @param  DateTime  $paymentDate  Undocumented variable
     *
     * @return  self
     */
    public function setPaymentDate(DateTime $paymentDate)
    {
        $this->paymentDate = $paymentDate;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  float
     */
    public function getCashAmount()
    {
        return $this->cashAmount;
    }

    /**
     * Set undocumented variable
     *
     * @param  float  $cashAmount  Undocumented variable
     *
     * @return  self
     */
    public function setCashAmount(float $cashAmount)
    {
        $this->cashAmount = $cashAmount;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  float
     */
    public function getForwardNetDividend()
    {
        return $this->forwardNetDividend;
    }

    /**
     * Set undocumented variable
     *
     * @param  float  $forwardNetDividend  Undocumented variable
     *
     * @return  self
     */
    public function setForwardNetDividend(float $forwardNetDividend)
    {
        $this->forwardNetDividend = $forwardNetDividend;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  float
     */
    public function getForwardNetDividendYield()
    {
        return $this->forwardNetDividendYield;
    }

    /**
     * Set undocumented variable
     *
     * @param  float  $forwardNetDividendYield  Undocumented variable
     *
     * @return  self
     */
    public function setForwardNetDividendYield(float $forwardNetDividendYield)
    {
        $this->forwardNetDividendYield = $forwardNetDividendYield;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  Currency
     */
    public function getCashCurrency()
    {
        return $this->cashCurrency;
    }

    /**
     * Set undocumented variable
     *
     * @param  Currency  $cashCurrency  Undocumented variable
     *
     * @return  self
     */
    public function setCashCurrency(Currency $cashCurrency)
    {
        $this->cashCurrency = $cashCurrency;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  float
     */
    public function getPercentageAllocation()
    {
        return $this->percentageAllocation;
    }

    /**
     * Set undocumented variable
     *
     * @param  float  $percentageAllocation  Undocumented variable
     *
     * @return  self
     */
    public function setPercentageAllocation(float $percentageAllocation)
    {
        $this->percentageAllocation = $percentageAllocation;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getSymbol()
    {
        return $this->symbol;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $symbol  Undocumented variable
     *
     * @return  self
     */
    public function setSymbol(string $symbol)
    {
        $this->symbol = $symbol;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $fullname  Undocumented variable
     *
     * @return  self
     */
    public function setFullname(string $fullname)
    {
        $this->fullname = $fullname;

        return $this;
    }

    /**
     * Get market price
     *
     * @return  float
     */
    public function getMarketPrice(): ?float
    {
        return $this->marketPrice;
    }

    /**
     * Set market price
     *
     * @param  float  $marketPrice  Market price
     *
     * @return  self
     */
    public function setMarketPrice(?float $marketPrice)
    {
        $this->marketPrice = $marketPrice;

        return $this;
    }

    /**
     * Get pies
     *
     * @return  Collection
     */
    public function getPies()
    {
        return $this->pies;
    }

    /**
     * Set pies
     *
     * @param  Collection  $pies  Pies
     *
     * @return  self
     */
    public function setPies(Collection $pies)
    {
        $this->pies = $pies;

        return $this;
    }

    /**
     * Get has a calendar entry
     *
     * @return  boolean
     */
    public function hasDivDate(): bool
    {
        return $this->divDate;
    }

    /**
     * Set has a calendar entry
     *
     * @param  boolean  $divDate  Has a calendar entry
     *
     * @return  self
     */
    public function setDivDate(bool $divDate)
    {
        $this->divDate = $divDate;

        return $this;
    }

    /**
     * Get position allocation
     *
     * @return  float
     */
    public function getAllocation()
    {
        return $this->allocation;
    }

    /**
     * Set position allocation
     *
     * @param  float  $allocation  Position allocation
     *
     * @return  self
     */
    public function setAllocation(float $allocation)
    {
        $this->allocation = $allocation;

        return $this;
    }

    /**
     * Get how many shares
     *
     * @return  float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set how many shares
     *
     * @param  float  $amount  How many shares
     *
     * @return  self
     */
    public function setAmount(float $amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get total received dividends
     *
     * @return  float
     */
    public function getDividend()
    {
        return $this->dividend;
    }

    /**
     * Set total received dividends
     *
     * @param  float  $dividend  Total received dividends
     *
     * @return  self
     */
    public function setDividend(float $dividend)
    {
        $this->dividend = $dividend;

        return $this;
    }

    /**
     * Get ticker id
     *
     * @return  int
     */
    public function getTickerId()
    {
        return $this->tickerId;
    }

    /**
     * Set ticker id
     *
     * @param  int  $tickerId  Ticker id
     *
     * @return  self
     */
    public function setTickerId(int $tickerId)
    {
        $this->tickerId = $tickerId;

        return $this;
    }

    /**
     * Get current dividend month?
     *
     * @return  boolean
     */
    public function isDividendMonth(): bool
    {
        return $this->isDividendMonth;
    }

    /**
     * Set current dividend month?
     *
     * @param  boolean  $isDividendMonth  Current dividend month?
     *
     * @return  self
     */
    public function setIsDividendMonth(bool $isDividendMonth)
    {
        $this->isDividendMonth = $isDividendMonth;

        return $this;
    }

    /**
     * Get position id
     *
     * @return  int
     */
    public function getPositionId()
    {
        return $this->positionId;
    }

    /**
     * Set position id
     *
     * @param  int  $positionId  Position id
     *
     * @return  self
     */
    public function setPositionId(int $positionId)
    {
        $this->positionId = $positionId;

        return $this;
    }

    /**
     * Get diffrence between avg price and market price
     *
     * @return  float
     */
    public function getDiffPrice()
    {
        return $this->diffPrice;
    }

    /**
     * Set diffrence between avg price and market price
     *
     * @param  float  $diffPrice  Diffrence between avg price and market price
     *
     * @return  self
     */
    public function setDiffPrice(float $diffPrice)
    {
        $this->diffPrice = $diffPrice;

        return $this;
    }

    /**
     * Get how times per year will there be a dividend payout
     *
     * @return  int
     */
    public function getDividendPayoutFrequency()
    {
        return $this->dividendPayoutFrequency;
    }

    /**
     * Set how times per year will there be a dividend payout
     *
     * @param  int  $dividendPayoutFrequency  How times per year will there be a dividend payout
     *
     * @return  self
     */
    public function setDividendPayoutFrequency(int $dividendPayoutFrequency)
    {
        $this->dividendPayoutFrequency = $dividendPayoutFrequency;

        return $this;
    }

    /**
     * Get collection of dividend calenders of future payments
     *
     * @return  array
     */
    public function getDividendCalendars(): array
    {
        return array_reverse($this->dividendCalendars->toArray());
    }

    /**
     * Add future calendar to collection
     *
     * @param  Calendar  $dividendCalendar
     *
     * @return  self
     */
    public function addDividendCalendar(Calendar $dividendCalendar): self
    {
        if (!$this->dividendCalendars->contains($dividendCalendar)) {
            $this->dividendCalendars->add($dividendCalendar);
        }

        return $this;
    }

    /**
     * Get current dividend yield per share based on current marketprice
     *
     * @return  float
     */ 
    public function getForwardNetDividendYieldPerShare(): float
    {
        return $this->forwardNetDividendYieldPerShare ?? 0;
    }

    /**
     * Set current dividend yield per share based on current marketprice
     *
     * @param  float  $forwardNetDividendYieldPerShare  Current dividend yield per share based on current marketprice
     *
     * @return  self
     */ 
    public function setForwardNetDividendYieldPerShare(float $forwardNetDividendYieldPerShare): self
    {
        $this->forwardNetDividendYieldPerShare = $forwardNetDividendYieldPerShare;

        return $this;
    }
}
