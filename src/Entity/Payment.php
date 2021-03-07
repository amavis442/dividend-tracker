<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PaymentRepository")
 * @ORM\HasLifecycleCallbacks
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
     * Undocumented variable
     *
     * @var float
     */
    private $taxes;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\Position", inversedBy="payments")
     * @ORM\JoinColumn(nullable=true)
     */
    private $position;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $amount;

    /**
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", name="updated_at", nullable = true)
     */
    private $updatedAt;

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

    public function getTaxes(): ?float
    {
        $taxes = ($this->dividend / 85) * 15;
        return $taxes;
    }

    public function setTicker(?Ticker $ticker): self
    {
        $this->ticker = $ticker;

        return $this;
    }

    public function getTicker(): ?Ticker
    {
        return $this->ticker;
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

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(?int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function setPosition(Position $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getPosition(): ?Position
    {
        return $this->position;
    }

    /**
     * Gets triggered only on insert
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->createdAt = new \DateTime("now");
    }

    public function setCreatedAt(DateTimeInterface $createdAt = null): self
    {
        $this->createdAt = $createdAt ?? new DateTime("now");
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Gets triggered every time on update
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->updatedAt = new \DateTime("now");
    }

    public function setUpdatedAt(DateTimeInterface $updatedAt = null): self
    {
        $this->updatedAt = $updatedAt ?? new DateTime("now");
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }
}
