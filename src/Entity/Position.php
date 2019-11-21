<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PositionRepository")
 */
class Position
{
    public const BROKERS = ['eToro', 'Trading212', 'Flatex'];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $price;

    /**
     * @ORM\Column(type="integer")
     */
    private $amount;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Ticker", inversedBy="positions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $ticker;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Payment", mappedBy="position")
     */
    private $payments;

    /**
     * @ORM\Column(type="datetime", name="buy_date")
     */
    private $buyDate;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $closed;

    /**
     * @ORM\Column(type="datetime", nullable=true, name="close_date")
     */
    private $closeDate;

    /**
     * @ORM\Column(type="integer", nullable=true, name="close_price")
     */
    private $closePrice;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $profit;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $allocation;

    /** @var int */
    private $dividend = 0;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="positions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency")
     * @ORM\JoinColumn(nullable=false)
     */
    private $currency;

    /**
     * @ORM\Column(type="string", length=255,  options={"default" : "Trading212"})
     */
    private $broker;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency")
     */
    private $allocationCurrency;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency")
     */
    private $closedCurrency;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getTicker(): ?Ticker
    {
        return $this->ticker;
    }

    public function setTicker(?Ticker $ticker): self
    {
        $this->ticker = $ticker;

        return $this;
    }

    /**
     * @return Collection|Payment[]
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): self
    {
        if (!$this->payments->contains($payment)) {
            $this->payments[] = $payment;
            $payment->setPosition($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        if ($this->payments->contains($payment)) {
            $this->payments->removeElement($payment);
            // set the owning side to null (unless already changed)
            if ($payment->getPosition() === $this) {
                $payment->setPosition(null);
            }
        }

        return $this;
    }

    public function getBuyDate(): ?\DateTimeInterface
    {
        return $this->buyDate;
    }

    public function setBuyDate(\DateTimeInterface $buyDate): self
    {
        $this->buyDate = $buyDate;

        return $this;
    }

    public function getClosed(): ?bool
    {
        return $this->closed;
    }

    public function setClosed(?bool $closed): self
    {
        $this->closed = $closed;
        return $this;
    }

    public function getCloseDate(): ?\DateTimeInterface
    {
        return $this->closeDate;
    }

    public function setCloseDate(?\DateTimeInterface $closeDate): self
    {
        $this->closeDate = $closeDate;

        return $this;
    }

    public function getClosePrice(): ?int
    {
        return $this->closePrice;
    }

    public function setClosePrice(int $closePrice): self
    {
        $this->closePrice = $closePrice;
        if ($this->closePrice > 0) {
            $this->profit = round((($this->closePrice - $this->price) * $this->amount) / 100);
        }
        return $this;
    }

    public function getProfit(): ?float
    {
        if ($this->closed == 1) {
            return (($this->closePrice - $this->price) * $this->amount) / 10000;
        }

        return $this->profit;
    }

    public function getProfitPercentage(): ?float
    {
        if ($this->closed == 1 && $this->allocation > 0) {
            return ((($this->closePrice - $this->price) * $this->amount) / $this->allocation);
        }
        return null;
    }

    public function getAllocated(): int
    {
        return (int) round(($this->amount * $this->price) / 10000);
    }

    public function getDividend(): int
    {
        $result = 0;
        foreach ($this->payments as $payment) {
            $result += $payment->getDividend();
        }
        $this->dividend = $result;
        return $result;
    }

    public function getDividendYield(): float
    {
        $result = 0;
        if ($this->dividend > 0 && $this->allocation > 0) {
            $result = ($this->dividend / $this->allocation) * 100;
        }
        return $result;
    }

    public function getAllocation(): ?int
    {
        return $this->allocation;
    }

    public function setAllocation(?int $allocation): self
    {
        $this->allocation = $allocation;

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

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getBroker(): ?string
    {
        return $this->broker;
    }

    public function setBroker(string $broker): self
    {
        $this->broker = $broker;

        return $this;
    }

    public function getAllocationCurrency(): ?Currency
    {
        return $this->allocationCurrency;
    }

    public function setAllocationCurrency(?Currency $allocationCurrency): self
    {
        $this->allocationCurrency = $allocationCurrency;

        return $this;
    }

    public function getClosedCurrency(): ?Currency
    {
        return $this->closedCurrency;
    }

    public function setClosedCurrency(?Currency $closedCurrency): self
    {
        $this->closedCurrency = $closedCurrency;

        return $this;
    }
}
