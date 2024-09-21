<?php

namespace App\Entity;

class SearchForm
{
    /**
     *
     * @var Pie
     */
    private ?Pie $pie = null;

    private ?Ticker $ticker = null;

    public function getPie(): ?Pie
    {
        return $this->pie;
    }

    public function setPie(?Pie $pie): static
    {
        $this->pie = $pie ?? new Pie();

        return $this;
    }

    /**
     * Get the value of search
     */
    public function getTicker(): ?Ticker
    {
        return $this->ticker;
    }

    /**
     * Set the value of search
     *
     * @return  static
     */
    public function setTicker(?Ticker $ticker): static
    {
        $this->ticker = $ticker ?? new Ticker();

        return $this;
    }
}
