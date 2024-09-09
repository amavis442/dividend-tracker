<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['dividendmonth:read']],
    denormalizationContext: ['groups' => ['dividendmonth:write']],
    security: 'is_granted("ROLE_USER")',
    operations: [
        new Get(),
        new GetCollection()
    ]
)]
#[ORM\Entity(repositoryClass: 'App\Repository\DividendMonthRepository')]
#[ORM\Index(columns: ['dividend_month'], name: 'dividend_month_idx')]
class DividendMonth
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    private ?int $id = null;

    #[Groups('dividendmonth:read', 'dividendmonth:write', 'ticker:read:item', 'position:read:item')]
    #[ORM\Column(type: 'integer',)]
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
