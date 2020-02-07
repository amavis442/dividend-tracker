<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DividendMonthRepository")
 */
class DividendMonth
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $dividendMonth;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Ticker", mappedBy="DividendMonths")
     */
    private $tickers;

    public function __construct()
    {
        $this->tickers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDividendMonth(): ?int
    {
        return $this->dividendMonth;
    }

    public function getDividendMonthName(): string
    {
        if ($this->dividendMonth < 0 || $this->dividendMonth > 12) {
            return '';
        }
        $dateObj   = \DateTime::createFromFormat('!m', $this->dividendMonth);
        return $dateObj->format('F'); // March
    }

    public function setDividendMonth(int $dividendMonth): self
    {
        $this->dividendMonth = $dividendMonth;

        return $this;
    }

    /**
     * @return Collection|Ticker[]
     */
    public function getTickers(): Collection
    {
        return $this->tickers;
    }

    public function addTicker(Ticker $ticker): self
    {
        if (!$this->tickers->contains($ticker)) {
            $this->tickers[] = $ticker;
            $ticker->addDividendMonth($this);
        }

        return $this;
    }

    public function removeTicker(Ticker $ticker): self
    {
        if ($this->tickers->contains($ticker)) {
            $this->tickers->removeElement($ticker);
            $ticker->removeDividendMonth($this);
        }

        return $this;
    }
}
