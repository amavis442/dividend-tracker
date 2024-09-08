<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: 'App\Repository\PaymentRepository')]
#[ORM\HasLifecycleCallbacks]
class Payment
{

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', name: 'pay_date')]
    private $payDate;

    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private $dividend = 0.0;

    private $taxes;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Ticker', inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private $ticker;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Calendar', inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: true)]
    private $calendar;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\User', inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Currency')]
    #[ORM\JoinColumn(nullable: false)]
    private $currency;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Position', inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private $position;

    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private $amount = 0.0;

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    private $createdAt;

    #[ORM\Column(type: 'datetime', name: 'updated_at', nullable: true)]
    private $updatedAt;

    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private $taxWithold = 0.0;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $taxCurrency;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $dividendType;

    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private $dividendPaid = 0.0;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $dividendPaidCurrency;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $uuid = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $importfile = null;

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

    public function getDividend(): float
    {
        return $this->dividend;
    }

    public function setDividend(float $dividend): self
    {
        $this->dividend = $dividend;

        return $this;
    }

    public function getTaxes(): float
    {
        $this->taxes = ($this->dividend / (100 - Constants::TAX)) * Constants::TAX;

        return $this->taxes;
    }

    public function setTicker(Ticker $ticker): self
    {
        $this->ticker = $ticker;

        return $this;
    }

    public function getTicker(): Ticker
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

    public function getUser(): ?User
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

    public function setCurrency(Currency $currency): self
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

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function setPosition(Position $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getPosition(): Position
    {
        return $this->position;
    }

    /**
     * Gets triggered only on insert
     */
    #[ORM\PrePersist]
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
     */
    #[ORM\PreUpdate]
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

    public function getTaxWithold(): float
    {
        return $this->taxWithold;
    }

    public function setTaxWithold(float $taxWithold): self
    {
        $this->taxWithold = $taxWithold;

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

    public function getDividendPaid(): float
    {
        return $this->dividendPaid;
    }

    public function setDividendPaid(float $dividendPaid): self
    {
        $this->dividendPaid = $dividendPaid;

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

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function setUuid(?Uuid $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getImportfile(): ?string
    {
        return $this->importfile;
    }

    public function setImportfile(?string $importfile): static
    {
        $this->importfile = $importfile;

        return $this;
    }
}
