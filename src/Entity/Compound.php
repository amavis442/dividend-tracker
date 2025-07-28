<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class Compound
{
    /**
     * Number of shares
     *
     * @var float
     */
    #[Assert\GreaterThan(0)]
    private float $amount;
    /**
     * Starting price and will be higher each year depending price Appreciation
     *
     * @var float
     */
    #[Assert\GreaterThan(0)]
    private float $price;
    /**
     * Start dividend yield
     *
     * @var float
     */
    #[Assert\GreaterThan(0)]
    private float $dividend;
    /**
     * Rise of market gain in percentage per year around 7.43%
     *
     * @var float
     */
    private float $priceAppreciation = 0.0;

    /**
     * Maximum price
     *
     * @var float
     */
    private float $maxPrice = 0.0;

    /**
     * Dividend growth for the first 5 years
     *
     * @var float
     */
    private float $growth = 0.0;
    /**
     * Growth after 5 years and into infinity will normally be around 3%
     *
     * @var float
     */
    private float $growthAfter5Years = 0.0;
    /**
     * How many times does a company pay dividends per year. Default will be 4
     *
     * @var int
     */
    private int $frequency = 4;

    /**
     * Extra per month
     *
     * @var float
     */
    private float $extraPerMonth = 0.0;

    /**
     *
     * @var int
     */
    private int $years = 5;

    /**
     *
     * @var float
     */
    private float $taxRate = 15;

    /**
     *
     * @var float
     */
    #[Assert\GreaterThan(0.7)]
    private $exchangeRate = 1.2;

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setDividend(float $dividend): self
    {
        $this->dividend = $dividend;

        return $this;
    }

    public function getDividend(): float
    {
        return $this->dividend;
    }

    public function setGrowth(?float $growth): self
    {
        $this->growth = $growth;

        return $this;
    }

    public function getGrowth(): ?float
    {
        return $this->growth;
    }

    public function setExtraPerMonth(float $extraPerMonth): self
    {
        $this->extraPerMonth = $extraPerMonth;

        return $this;
    }

    public function getExtraPerMonth(): float
    {
        return $this->extraPerMonth;
    }


    /**
     * Get rise of market gain in percentage per year around 7.43%
     *
     * @return  float|null
     */
    public function getPriceAppreciation(): ?float
    {
        return $this->priceAppreciation;
    }

    /**
     * Set rise of market gain in percentage per year around 7.43%
     *
     * @param  float  $priceAppreciation  Rise of market gain in percentage per year around 7.43%
     *
     * @return  self
     */
    public function setPriceAppreciation(float $priceAppreciation): self
    {
        $this->priceAppreciation = $priceAppreciation;

        return $this;
    }

    /**
     * Get growth after 5 years and into infinity will normally be around 3%
     *
     * @return  float|null
     */
    public function getGrowthAfter5Years(): ?float
    {
        return $this->growthAfter5Years;
    }

    /**
     * Set growth after 5 years and into infinity will normally be around 3%
     *
     * @param  float $growthAfter5Years  Growth after 5 years and into infinity will normally be around 3%
     *
     * @return  self
     */
    public function setGrowthAfter5Years(?float $growthAfter5Years): self
    {
        $this->growthAfter5Years = $growthAfter5Years;

        return $this;
    }

    /**
     * Get maximum price
     *
     * @return  float|null
     */
    public function getMaxPrice(): ?float
    {
        return $this->maxPrice;
    }

    /**
     * Set maximum price
     *
     * @param  float  $maxPrice  Maximum price
     *
     * @return  self
     */
    public function setMaxPrice(?float $maxPrice): self
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
    public function getTaxRate(): float
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
    public function getExchangeRate(): float
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
