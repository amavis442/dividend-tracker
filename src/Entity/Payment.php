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

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $taxWithold;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $taxCurrency;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dividendType;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $dividendPaid;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dividendPaidCurrency;

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

    public function getDividend(): ?float
    {
        return $this->dividend / Constants::VALUTA_PRECISION;
    }

    public function setDividend(float $dividend): self
    {
        $this->dividend = $dividend * Constants::VALUTA_PRECISION;

        return $this;
    }

    public function getTaxes(): ?float
    {
        $this->taxes = ($this->dividend / (100 - Constants::TAX)) * Constants::TAX;

        return $this->taxes / Constants::VALUTA_PRECISION;
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

    public function getAmount(): ?string
    {
        return $this->amount / Constants::AMOUNT_PRECISION;
    }

    public function setAmount(?string $amount): self
    {
        $this->amount = $amount * Constants::AMOUNT_PRECISION;

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

        return $this;
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

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getTaxWithold(): ?float
    {
        return $this->taxWithold / Constants::VALUTA_PRECISION;
    }

    public function setTaxWithold(?float $taxWithold): self
    {
        $this->taxWithold = $taxWithold * Constants::VALUTA_PRECISION;

        return $this;
    }

    public function getTaxCurrency(): ?string
    {
        return $this->taxCurrency;
    }

    public function setTaxCurrency(?string $taxCurrency): self
    {
        $this->taxCurrency = $taxCurrency;

        return $this;
    }

    public function getDividendType(): ?string
    {
        return $this->dividendType;
    }

    public function setDividendType(?string $dividendType): self
    {
        $this->dividendType = $dividendType;

        return $this;
    }

    public function getDividendPaid(): ?float
    {
        return $this->dividendPaid / Constants::VALUTA_PRECISION;
    }

    public function setDividendPaid(?float $dividendPaid): self
    {
        $this->dividendPaid = $dividendPaid * Constants::VALUTA_PRECISION;

        return $this;
    }

    public function getDividendPaidCurrency(): ?string
    {
        return $this->dividendPaidCurrency;
    }

    public function setDividendPaidCurrency(?string $dividendPaidCurrency): self
    {
        $this->dividendPaidCurrency = $dividendPaidCurrency;

        return $this;
    }
}
