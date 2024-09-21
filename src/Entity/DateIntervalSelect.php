<?php

namespace App\Entity;

class DateIntervalSelect
{
    private ?int $year = null;
    private ?int $month = null;
    private ?int $quator = null;
    private ?Ticker $ticker = null;

    /**
     * Get the value of year
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * Set the value of year
     *
     * @return  static
     */
    public function setYear(int $year): static
    {
        $this->year = $year;

        return $this;
    }

    /**
     * Get the value of month
     */
    public function getMonth(): int
    {
        return $this->month;
    }

    /**
     * Set the value of month
     *
     * @return  static
     */
    public function setMonth(int $month): static
    {
        $this->month = $month;

        return $this;
    }

    /**
     * Get the value of quator
     */
    public function getQuator(): int
    {
        return $this->quator;
    }

    /**
     * Set the value of quator
     *
     * @return  static
     */
    public function setQuator(int $quator): static
    {
        $this->quator = $quator;

        return $this;
    }

    /**
     * Get the value of ticker
     */
    public function getTicker(): ?Ticker
    {
        return $this->ticker;
    }

    /**
     * Set the value of ticker
     *
     * @return  static
     */
    public function setTicker(Ticker $ticker): static
    {
        $this->ticker = $ticker;

        return $this;
    }
}
