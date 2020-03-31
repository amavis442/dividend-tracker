<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TickerRepository")
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
     * @ORM\OneToMany(targetEntity="App\Entity\Position", mappedBy="ticker")
     */
    private $positions;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Calendar", mappedBy="ticker")
     * @ORM\OrderBy({"paymentDate" = "DESC"})
     */
    private $calendars;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Payment", mappedBy="ticker", orphanRemoval=true)
     * @ORM\OrderBy({"payDate" = "DESC"})
     */
    private $payments;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="tickers")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Research", mappedBy="ticker")
     */
    private $researches;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\DividendMonth", inversedBy="tickers")
     */
    private $DividendMonths;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Transaction", mappedBy="ticker")
     */
    private $transactions;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $isin;

    public function __construct()
    {
        $this->positions = new ArrayCollection();
        $this->calendars = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->researches = new ArrayCollection();
        $this->dividendMonths = new ArrayCollection();
        $this->DividendMonths = new ArrayCollection();
        $this->transactions = new ArrayCollection();
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
     * @return Collection|Position[]
     */
    public function getPositions(): Collection
    {
        return $this->positions;
    }

    public function addPosition(Position $position): self
    {
        if (!$this->positions->contains($position)) {
            $this->positions[] = $position;
            $position->setTicker($this);
        }

        return $this;
    }

    public function removePosition(Position $position): self
    {
        if ($this->positions->contains($position)) {
            $this->positions->removeElement($position);
            // set the owning side to null (unless already changed)
            if ($position->getTicker() === $this) {
                $position->setTicker(null);
            }
        }

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
     * @return Collection|Payment[]
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function getSumDividend(): ?int
    {
        if ($this->payments->count() < 1) {
            return null;
        }
        $result = 0;
        foreach ($this->payments as $payment) {
            $result += $payment->getDividend();
        }
        return $result;
    }

    public function getSumAllocation(): ?int
    {
        if ($this->positions->count() < 1) {
            return null;
        }
        $result = 0;
        foreach ($this->positions as $position) {
            if ($position->getClosed() <> 1) {
                $result += $position->getAllocation();
            }
        }
        return $result;
    }

    public function getSummary(): ?array
    {
        $allocation = 0;
        $units = 0;
        $dividends = 0;
        $positions = 0;
        $price = 0;

        if (($positions = $this->positions->count()) > 0) {
            foreach ($this->positions as $position) {
                if ($position->getClosed() <> 1) {
                    $allocation += $position->getAllocation();
                    $units += $position->getAmount();
                    $price += $position->getPrice();
                }
            }
            $price = $price / $positions;
        }
        if ($this->payments->count() > 0) {
            foreach($this->payments as $payment) {
                $dividends += $payment->getDividend();
            }
        }
        return [
            'dividend' => $dividends,
            'positions' => $positions,
            'units' => $units,
            'allocation' => $allocation,
            'price' => $price
        ];
    }


    public function addPayment(Payment $payment): self
    {
        if (!$this->payments->contains($payment)) {
            $this->payments[] = $payment;
            $payment->setTicker($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        if ($this->payments->contains($payment)) {
            $this->payments->removeElement($payment);
            // set the owning side to null (unless already changed)
            if ($payment->getTicker() === $this) {
                $payment->setTicker(null);
            }
        }

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
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
    
    public function getActiveUnits(): int
    {
        $units = 0;
        if ($this->positions) {
            foreach ($this->positions as $position)
            {
                if ($position->getClosed() === 1) {
                    continue;   
                }
                $units += $position->getAmount();
            }
        }
        return $units;
    }

    /**
     * @return Collection|Transaction[]
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions[] = $transaction;
            $transaction->setTicker($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        if ($this->transactions->contains($transaction)) {
            $this->transactions->removeElement($transaction);
            // set the owning side to null (unless already changed)
            if ($transaction->getTicker() === $this) {
                $transaction->setTicker(null);
            }
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
