<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class Compound
{
    /**
     * Number of shares
     *
     * @var int|null
     */
    #[Assert\GreaterThan(0)]
    private $amount;
    /**
     * Starting price and will be higher each year depending price Appreciation
     *
     * @var int|null
     */
    #[Assert\GreaterThan(0)]
    private $price;
    /**
     * Start dividend yield
     *
     * @var int|null
     */
    #[Assert\GreaterThan(0)]
    private $dividend;
    /**
     * Rise of market gain in percentage per year around 7.43%
     *
     * @var int|null
     */
    private $priceAppreciation;

    /**
     * Maximum price
     *
     * @var int
     */
    private $maxPrice;

    /**
     * Dividend growth for the first 5 years
     *
     * @var int|null
     */
    private $growth;
    /**
     * Growth after 5 years and into infinity will normally be around 3%
     *
     * @var int|null
     */
    private $growthAfter5Years;
    /**
     * How many times does a company pay dividends per year. Default will be 4
     *
     * @var int|null
     */
    private $frequency;

    /**
     *
     * @var int
     */
    private $years;

    /**
     *
     * @var float
     */
    private $taxRate = 15;

    /**
     *
     * @var float
     */
    private $exchangeRate = 1.2;

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setDividend(int $dividend): self
    {
        $this->dividend = $dividend;

        return $this;
    }

    public function getDividend(): ?int
    {
        return $this->dividend;
    }

    public function setGrowth(int $growth): self
    {
        $this->growth = $growth;

        return $this;
    }

    public function getGrowth(): ?int
    {
        return $this->growth;
    }

    /**
     * Get rise of market gain in percentage per year around 7.43%
     *
     * @return  int|null
     */
    public function getPriceAppreciation(): ?int
    {
        return $this->priceAppreciation;
    }

    /**
     * Set rise of market gain in percentage per year around 7.43%
     *
     * @param  int|null  $priceAppreciation  Rise of market gain in percentage per year around 7.43%
     *
     * @return  self
     */
    public function setPriceAppreciation(int $priceAppreciation): self
    {
        $this->priceAppreciation = $priceAppreciation;

        return $this;
    }

    /**
     * Get growth after 5 years and into infinity will normally be around 3%
     *
     * @return  int|null
     */
    public function getGrowthAfter5Years(): ?int
    {
        return $this->growthAfter5Years;
    }

    /**
     * Set growth after 5 years and into infinity will normally be around 3%
     *
     * @param  int|null  $growthAfter5Years  Growth after 5 years and into infinity will normally be around 3%
     *
     * @return  self
     */
    public function setGrowthAfter5Years(int $growthAfter5Years): self
    {
        $this->growthAfter5Years = $growthAfter5Years;

        return $this;
    }

    /**
     * Get maximum price
     *
     * @return  int|null
     */
    public function getMaxPrice(): ?int
    {
        return $this->maxPrice;
    }

    /**
     * Set maximum price
     *
     * @param  int  $maxPrice  Maximum price
     *
     * @return  self
     */
    public function setMaxPrice(int $maxPrice): self
    {
        $this->maxPrice = $maxPrice;

        return $this;
    }

    /**
     * Get how many times does a company pay dividends per year. Default will be 4
     *
     * @return  int|null
     */
    public function getFrequency(): ?int
    {
        return $this->frequency;
    }

    /**
     * Set how many times does a company pay dividends per year. Default will be 4
     *
     * @param  int|null  $frequency  How many times does a company pay dividends per year. Default will be 4
     *
     * @return  self
     */
    public function setFrequency($frequency): self
    {
        $this->frequency = $frequency;

        return $this;
    }

    /**
     * Get the value of years
     *
     * @return  int
     */
    public function getYears(): int
    {
        return $this->years;
    }

    /**
     * Set the value of years
     *
     * @param  int  $years
     *
     * @return  self
     */
    public function setYears(int $years): self
    {
        $this->years = $years;

        return $this;
    }

    /**
     * Get the value of taxRate
     *
     * @return  float
     */
    public function getTaxRate()
    {
        return $this->taxRate;
    }

    /**
     * Set the value of taxRate
     *
     * @param  float  $taxRate
     *
     * @return  self
     */
    public function setTaxRate(float $taxRate)
    {
        $this->taxRate = $taxRate;

        return $this;
    }

    /**
     * Get the value of exchangeRate
     *
     * @return  float
     */
    public function getExchangeRate()
    {
        return $this->exchangeRate;
    }

    /**
     * Set the value of exchangeRate
     *
     * @param  float  $exchangeRate
     *
     * @return  self
     */
    public function setExchangeRate(float $exchangeRate)
    {
        $this->exchangeRate = $exchangeRate;

        return $this;
    }
}
