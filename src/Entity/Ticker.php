<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TickerRepository")
 * @UniqueEntity("ticker")
 */
class Ticker
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $ticker;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $fullname;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Branch", inversedBy="tickers")
     * @ORM\JoinColumn(nullable=false)
     */
    private $branch;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Calendar", mappedBy="ticker")
     * @ORM\OrderBy({"paymentDate" = "DESC"})
     */
    private $calendars;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Research", mappedBy="ticker")
     */
    private $researches;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Payment", mappedBy="ticker")
     */
    private $payments;


    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\DividendMonth", inversedBy="tickers")
     */
    private $DividendMonths;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $isin;

    public function __construct()
    {
        $this->calendars = new ArrayCollection();
        $this->researches = new ArrayCollection();
        $this->dividendMonths = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTicker(): ?string
    {
        return $this->ticker;
    }

    public function setTicker(string $ticker): self
    {
        $this->ticker = strtoupper($ticker);

        return $this;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setFullname(string $fullname): self
    {
        $this->fullname = $fullname;

        return $this;
    }

    public function getBranch(): ?Branch
    {
        return $this->branch;
    }

    public function setBranch(?Branch $branch): self
    {
        $this->branch = $branch;

        return $this;
    }

    /**
     * @return Collection|Calendar[]
     */
    public function getCalendars(): Collection
    {
        return $this->calendars;
    }

    public function addCalendar(Calendar $calendar): self
    {
        if (!$this->calendars->contains($calendar)) {
            $this->calendars[] = $calendar;
            $calendar->setTicker($this);
        }

        return $this;
    }

    public function removeCalendar(Calendar $calendar): self
    {
        if ($this->calendars->contains($calendar)) {
            $this->calendars->removeElement($calendar);
            // set the owning side to null (unless already changed)
            if ($calendar->getTicker() === $this) {
                $calendar->setTicker(null);
            }
        }

        return $this;
    }
    public function getRecentDividendDate(): ?Calendar
    {
        if ($this->calendars->count() < 1) {
            return null;
        } 
        return $this->calendars[0];
    }

    /**
     * @return Collection|Research[]
     */
    public function getResearches(): Collection
    {
        return $this->researches;
    }

    public function addResearch(Research $research): self
    {
        if (!$this->researches->contains($research)) {
            $this->researches[] = $research;
            $research->setTicker($this);
        }

        return $this;
    }

    public function removeResearch(Research $research): self
    {
        if ($this->researches->contains($research)) {
            $this->researches->removeElement($research);
            // set the owning side to null (unless already changed)
            if ($research->getTicker() === $this) {
                $research->setTicker(null);
            }
        }

        return $this;
    }

    public function hasResearch(): bool
    {
        return $this->researches->count() > 0;
    }

    /**
     * @return Collection|DividendMonth[]
     */
    public function getDividendMonths(): Collection
    {
        return $this->DividendMonths;
    }

    public function addDividendMonth(DividendMonth $dividendMonth): self
    {
        if (!$this->DividendMonths->contains($dividendMonth)) {
            $this->DividendMonths[] = $dividendMonth;
        }

        return $this;
    }

    public function removeDividendMonth(DividendMonth $dividendMonth): self
    {
        if ($this->DividendMonths->contains($dividendMonth)) {
            $this->DividendMonths->removeElement($dividendMonth);
        }

        return $this;
    }
    
    public function getIsin(): ?string
    {
        return $this->isin;
    }

    public function setIsin(?string $isin): self
    {
        $this->isin = $isin;

        return $this;
    }
}
