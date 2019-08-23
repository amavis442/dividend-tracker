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
     * @ORM\Column(type="datetime")
     */
    private $buy_date;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $closed;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $close_date;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $close_price;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $profit;

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
        return $this->buy_date;
    }

    public function setBuyDate(\DateTimeInterface $buy_date): self
    {
        $this->buy_date = $buy_date;

        return $this;
    }

    public function getClosed(): ?bool
    {
        return $this->closed;
    }

    public function setClosed(?bool $closed): self
    {
        $this->closed = $closed;
        if ($this->closed === true) {
            $this->profit = round((($this->close_price - $this->price) * $this->amount) / 100);
        }

        return $this;
    }

    public function getCloseDate(): ?\DateTimeInterface
    {
        return $this->close_date;
    }

    public function setCloseDate(?\DateTimeInterface $close_date): self
    {
        $this->close_date = $close_date;

        return $this;
    }

    public function getClosePrice(): ?int
    {
        return $this->close_price;
    }

    public function setClosePrice(int $close_price): self
    {
        $this->close_price = $close_price;

        return $this;
    }

    public function getProfit(): ?float
    {
        if ($this->closed == 1){
            return (($this->close_price - $this->price) * $this->amount) / 10000;
        }

        return $this->profit;
    }
}
