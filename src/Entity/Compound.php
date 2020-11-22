<?php

namespace App\Entity;

class Compound
{
    private $amount;
    private $price;
    private $dividend;
    private $growth;

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
}
