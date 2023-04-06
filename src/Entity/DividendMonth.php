<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\DividendMonthRepository')]
class DividendMonth
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private $dividendMonth;

    #[ORM\ManyToMany(targetEntity: 'App\Entity\Ticker', mappedBy: 'dividendMonths')]
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

    /**
     * @deprecated 1.1
     */
    public function getDividendMonthName(): string
    {
        if ($this->dividendMonth < 0 || $this->dividendMonth > 12) {
            return '';
        }

        return date("F", strtotime(date("Y") . "-" . $this->dividendMonth . "-10"));
    }

    public function setDividendMonth(int $dividendMonth): self
    {
        $this->dividendMonth = $dividendMonth;

        return $this;
    }

    public function isDividendPayMonth(int $currentMonth): bool
    {
        if ($this->dividendMonth === (int) $currentMonth) {
            return true;
        }

        return false;
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
