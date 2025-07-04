<?php

namespace App\Entity;

class SearchForm
{

    private ?Ticker $ticker = null;

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
