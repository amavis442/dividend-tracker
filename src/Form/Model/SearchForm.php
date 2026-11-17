<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Ticker;

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
