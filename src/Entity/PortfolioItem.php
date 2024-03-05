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
    private \App\Entity\Position $position;
    /**
     * position avg price
     *
     * @var float
     */
    private float $price = 0.0;

    /**
     * Market price
     *
     * @var float
     */
    private float $marketPrice = 0.0;

    /**
     * Undocumented variable
     *
     * @var float
     */
    private float $paperProfit = 0.0;

    /**
     * Undocumented variable
     *
     * @var float
     */
    private float $paperProfitPercentage = 0.0;

    /**
     * Undocumented variable
     *
     * @var DateTime
     */
    private DateTime $exDividendDate;

    /**
     * Undocumented variable
     *
     * @var DateTime
     */
    private DateTime $paymentDate;

    /**
     * Undocumented variable
     *
     * @var float
     */
    private float $cashAmount = 0.0;

    /**
     * Undocumented variable
     *
     * @var Currency
     */
    private Currency $cashCurrency;

    /**
     * Undocumented variable
     *
     * @var float
     */
    private float $percentageAllocation = 0.0;

    /**
     * Undocumented variable
     *
     * @var float
     */
    private float $forwardNetDividend = 0.0;

    /**
     * Undocumented variable
     *
     * @var float
     */
    private float $forwardNetDividendYield = 0.0;

    /**
     * Current dividend yield per share based on current marketprice
     *
     * @var float
     */
    private float $forwardNetDividendYieldPerShare = 0.0;

    /**
     * Undocumented variable
     *
     * @var string
     */
    private string $symbol;

    /**
     * Undocumented variable
     *
     * @var string
     */
    private string $fullname;

    /**
     * Pies
     *
     * @var Collection
     */
    private Collection $pies;

    /**
     * Has a calendar entry
     *
     * @var bool
     */
    private bool $divDate = false;

    /**
     * Position allocation
     *
     * @var float
     */
    private float $allocation = 0.0;

    /**
     * How many shares
     *
     * @var float
     */
    private float $amount = 0.0;

    /**
     * Total received dividends
     *
     * @var float
     */
    private float $dividend = 0.0;

    /**
     * Ticker id
     *
     * @var int
     */
    private int $tickerId;

    /**
     * Position id
     *
     * @var int
     */
    private int $positionId;

    /**
     * Current dividend month?
     *
     * @var boolean
     */
    private bool $isDividendMonth = false;
    /**
     * Diffrence between avg price and market price
     *
     * @var float
     */
    private float $diffPrice = 0.0;

    /**
     * How times per year will there be a dividend payout
     *
     * @var int
     */
    private int $dividendPayoutFrequency = 4;

    /**
     * Collection of dividend calenders of future payments
     *
     * @var Collection
     */
    private Collection $dividendCalendars;

    /**
     *  Net dividend per share
     *
     * @var float
     */
    private float $netDividendPerShare  = 0.0;

    /**
     * What is the treshold for dividend yield start to buying
     *
     * @var float
     */
    private float $dividendTreshold  = 0.0;

    /**
     * Maximum allocation
     *
     * @var int
     */
    private int $maxAllocation = 0;

    /**
     * Has maximum allocation been reached
     *
     * @var bool
     */
    private bool $isMaxAllocation = false;

    public function __construct()
    {
        $this->dividendCalendars = new ArrayCollection();
        $this->isMaxAllocation = false;
    }

    /**
     * Get undocumented variable
     *
     * @return  \App\Entity\Position
     */
    public function getPosition(): Position
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
    public function setPosition(\App\Entity\Position $position): self
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  float
     */
    public function getPrice(): float
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
    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  float
     */
    public function getPaperProfit(): float
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
    public function setPaperProfit(float $paperProfit): self
    {
        $this->paperProfit = $paperProfit;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  float
     */
    public function getPaperProfitPercentage(): float
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
    public function setPaperProfitPercentage(float $paperProfitPercentage): self
    {
        $this->paperProfitPercentage = $paperProfitPercentage;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  DateTime
     */
    public function getExDividendDate(): DateTime
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
    public function setExDividendDate(DateTime $exDividendDate): self
    {
        $this->exDividendDate = $exDividendDate;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  DateTime
     */
    public function getPaymentDate(): DateTime
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
    public function setPaymentDate(DateTime $paymentDate): self
    {
        $this->paymentDate = $paymentDate;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  float
     */
    public function getCashAmount(): float
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
    public function setCashAmount(float $cashAmount): self
    {
        $this->cashAmount = $cashAmount;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  float
     */
    public function getForwardNetDividend(): float
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
    public function setForwardNetDividend(float $forwardNetDividend): self
    {
        $this->forwardNetDividend = $forwardNetDividend;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  float
     */
    public function getForwardNetDividendYield(): float
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
    public function setForwardNetDividendYield(float $forwardNetDividendYield): self
    {
        $this->forwardNetDividendYield = $forwardNetDividendYield;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  Currency
     */
    public function getCashCurrency(): Currency
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
    public function setCashCurrency(Currency $cashCurrency): self
    {
        $this->cashCurrency = $cashCurrency;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  float
     */
    public function getPercentageAllocation(): float
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
    public function setPercentageAllocation(float $percentageAllocation): self
    {
        $this->percentageAllocation = $percentageAllocation;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getSymbol(): string
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
    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getFullname(): string
    {
        return $this->fullname ?: '';
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $fullname  Undocumented variable
     *
     * @return  self
     */
    public function setFullname(string $fullname): self
    {
        $this->fullname = $fullname;

        return $this;
    }

    /**
     * Get market price
     *
     * @return  float
     */
    public function getMarketPrice(): float
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
    public function setMarketPrice(float $marketPrice): self
    {
        $this->marketPrice = $marketPrice;

        return $this;
    }

    /**
     * Get pies
     *
     * @return  Collection
     */
    public function getPies(): Collection
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
    public function setPies(Collection $pies): self
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
    public function setDivDate(bool $divDate): self
    {
        $this->divDate = $divDate;

        return $this;
    }

    /**
     * Get position allocation
     *
     * @return  float
     */
    public function getAllocation(): float
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
    public function setAllocation(float $allocation): self
    {
        $this->allocation = $allocation;

        return $this;
    }

    /**
     * Get how many shares
     *
     * @return  float
     */
    public function getAmount(): float
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
    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get total received dividends
     *
     * @return  float
     */
    public function getDividend(): float
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
    public function setDividend(float $dividend): self
    {
        $this->dividend = $dividend;

        return $this;
    }

    /**
     * Get ticker id
     *
     * @return  int
     */
    public function getTickerId(): int
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
    public function setTickerId(int $tickerId): self
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
    public function setIsDividendMonth(bool $isDividendMonth): self
    {
        $this->isDividendMonth = $isDividendMonth;

        return $this;
    }

    /**
     * Get position id
     *
     * @return  int
     */
    public function getPositionId(): int
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
    public function setPositionId(int $positionId): self
    {
        $this->positionId = $positionId;

        return $this;
    }

    /**
     * Get diffrence between avg price and market price
     *
     * @return  float
     */
    public function getDiffPrice(): float
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
    public function setDiffPrice(float $diffPrice): self
    {
        $this->diffPrice = $diffPrice;

        return $this;
    }

    /**
     * Get how times per year will there be a dividend payout
     *
     * @return  int
     */
    public function getDividendPayoutFrequency(): int
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
    public function setDividendPayoutFrequency(int $dividendPayoutFrequency): self
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

    /**
     * Get net dividend per share
     *
     * @return  null|float
     */
    public function getNetDividendPerShare(): float
    {
        return $this->netDividendPerShare ?? 0;
    }

    /**
     * Set net dividend per share
     *
     * @param  null|float  $netDividendPerShare  Net dividend per share
     *
     * @return  self
     */
    public function setNetDividendPerShare($netDividendPerShare): self
    {
        $this->netDividendPerShare = $netDividendPerShare;

        return $this;
    }

    /**
     * Get what is the treshold for dividend yield start to buying
     *
     * @return  null|float
     */
    public function getDividendTreshold(): float
    {
        return $this->dividendTreshold;
    }

    /**
     * Set what is the treshold for dividend yield start to buying
     *
     * @param  float  $dividendTreshold  What is the treshold for dividend yield start to buying
     *
     * @return  self
     */
    public function setDividendTreshold(float $dividendTreshold): self
    {
        $this->dividendTreshold = $dividendTreshold;

        return $this;
    }

    /**
     * Get maximum allocation
     *
     * @return  int
     */
    public function getMaxAllocation(): int
    {
        return $this->maxAllocation;
    }

    /**
     * Set maximum allocation
     *
     * @param  int  $maxAllocation  Maximum allocation
     *
     * @return  self
     */
    public function setMaxAllocation(int $maxAllocation): self
    {
        $this->maxAllocation = $maxAllocation;

        return $this;
    }

    /**
     * Get has maximum allocation been reached
     *
     * @return  bool
     */
    public function getIsMaxAllocation(): bool
    {
        return $this->isMaxAllocation;
    }

    /**
     * Set has maximum allocation been reached
     *
     * @param  bool  $isMaxAllocation  Has maximum allocation been reached
     *
     * @return  self
     */
    public function setIsMaxAllocation(bool $isMaxAllocation): self
    {
        $this->isMaxAllocation = $isMaxAllocation;

        return $this;
    }
}
