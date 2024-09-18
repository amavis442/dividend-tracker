<?php

namespace App\Entity;

class TickerAutocomplete
{
  private Ticker $ticker;

  /**
   * Get the value of search
   */
  public function getTicker(): ?Ticker
  {
    return $this->ticker ?? null;
  }

  /**
   * Set the value of search
   *
   * @return  self
   */
  public function setTicker(?Ticker $ticker): static
  {
    $this->ticker = $ticker;

    return $this;
  }
}
