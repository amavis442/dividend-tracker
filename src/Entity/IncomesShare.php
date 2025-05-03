<?php

namespace App\Entity;

class IncomesShare
{
    private ?string $fullname = null;

    private ?string $isin = null;

    private ?float $price = 1.0;

    private ?float $profitLoss = 0.0;

    private ?float $totalReturn = 0.0;

    public function getIsin(): ?string
    {
        return $this->isin;
    }

    public function setIsin(string $isin): static
    {
        $this->isin = $isin;

        return $this;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setFullname(string $fullname): static
    {
        $this->fullname = $fullname;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getProfitLoss(): ?float
    {
        return $this->profitLoss ?? 0.0;
    }

    public function setProfitLoss(float $profitLoss): static
    {
        $this->profitLoss = $profitLoss;

        return $this;
    }

    public function getTotalReturn(): ?float
    {
        return $this->totalReturn;
    }

    public function setTotalReturn(?float $totalReturn): static
    {
        $this->totalReturn = $totalReturn;

        return $this;
    }
}
