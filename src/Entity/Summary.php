<?php

namespace App\Entity;

class Summary
{
    private int $numActivePosition = 0;
    private int $numTickers = 0;
    private float $profit = 0.0;
    private float $totalDividend = 0.0;
    private float $allocated = 0.0;

    /**
     * Get the value of numActivePosition
     */
    public function getNumActivePosition(): int
    {
        return $this->numActivePosition;
    }

    /**
     * Set the value of numActivePosition
     */
    public function setNumActivePosition(int $numActivePosition): static
    {
        $this->numActivePosition = $numActivePosition;

        return $this;
    }

    /**
     * Get the value of numTickers
     */
    public function getNumTickers(): int
    {
        return $this->numTickers;
    }

    /**
     * Set the value of numTickers
     */
    public function setNumTickers(int $numTickers): static
    {
        $this->numTickers = $numTickers;

        return $this;
    }

    /**
     * Get the value of profit
     */
    public function getProfit(): float
    {
        return $this->profit;
    }

    /**
     * Set the value of profit
     */
    public function setProfit(float $profit): static
    {
        $this->profit = $profit;

        return $this;
    }

    /**
     * Get the value of totalDividend
     */
    public function getTotalDividend(): float
    {
        return $this->totalDividend;
    }

    /**
     * Set the value of totalDividend
     */
    public function setTotalDividend(float $totalDividend): static
    {
        $this->totalDividend = $totalDividend;

        return $this;
    }

    /**
     * Get the value of allocated
     */
    public function getAllocated(): float
    {
        return $this->allocated;
    }

    /**
     * Set the value of allocated
     */
    public function setAllocated(float $allocated): static
    {
        $this->allocated = $allocated;

        return $this;
    }
}
