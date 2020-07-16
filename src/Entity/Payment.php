<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PaymentRepository")
 */
class Payment
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

   /**
     * @ORM\Column(type="datetime", name = "pay_date")
     */
    private $payDate;

    /**
     * @ORM\Column(type="integer")
     */
    private $dividend;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Ticker", inversedBy="payments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $ticker;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Calendar", inversedBy="payments")
     * @ORM\JoinColumn(nullable=true)
     */
    private $calendar;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="payments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency")
     * @ORM\JoinColumn(nullable=false)
     */
    private $currency;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $stocks;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     */
    private $broker;

    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getPayDate(): ?\DateTimeInterface
    {
        return $this->payDate;
    }

    public function setPayDate(\DateTimeInterface $payDate): self
    {
        $this->payDate = $payDate;

        return $this;
    }

    public function getDividend(): ?int
    {
        return $this->dividend;
    }

    public function setDividend(int $dividend): self
    {
        $this->dividend = $dividend;

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
     * @return Calendar
     */
    public function getCalendar(): ?Calendar
    {
        return $this->calendar;
    }

    public function setCalendar(?Calendar $calendar = null): self
    {
        $this->calendar = $calendar;

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

    public function hasexDividendDate(): bool
    {
        return $this->calendar !== null;
    }
    
    public function hasrecordDate(): bool
    {
        return $this->calendar !== null;
    }

    public function getStocks(): ?int
    {
        return $this->stocks;
    }

    public function setStocks(?int $stocks): self
    {
        $this->stocks = $stocks;

        return $this;
    }

    public function getBroker(): ?string
    {
        return $this->broker;
    }

    public function setBroker(?string $broker): self
    {
        $this->broker = $broker;

        return $this;
    }
}
