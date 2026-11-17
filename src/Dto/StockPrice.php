<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * Plain value object for a market price, served by ApiController.
 * Not a Doctrine entity; candidate to move to App\Dto.
 */
class StockPrice
{
    private string $symbol = '';

    /**
     * Market price
     */
    private ?float $price = null;

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;

        return $this;
    }
}
