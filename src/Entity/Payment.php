<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[
    ApiResource(
        normalizationContext: ['groups' => ['payment:read']],
        denormalizationContext: ['groups' => ['payment:write']],
        security: 'is_granted("ROLE_USER")',
        operations: [new Get(), new GetCollection()]
    )
]
#[ORM\Entity(repositoryClass: 'App\Repository\PaymentRepository')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'mdhash_idx', fields: ['mdHash'])]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Groups(['payment:read', 'payment:write', 'position:read:item'])]
    #[ORM\Column(type: 'datetime', name: 'pay_date')]
    private DateTime $payDate;

    #[Groups(['payment:read', 'payment:write', 'position:read:item'])]
    #[ORM\Column(type: 'float', nullable: false, options: ['default' => 0])]
    private float $dividend = 0.0;

    private $taxes;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Ticker', inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private Ticker $ticker;

    #[
        ORM\ManyToOne(
            targetEntity: 'App\Entity\Calendar',
            inversedBy: 'payments'
        )
    ]
    #[ORM\JoinColumn(nullable: true)]
    private ?Calendar $calendar;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\User', inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[Groups(['payment:read', 'payment:write', 'position:read:item'])]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Currency')]
    #[ORM\JoinColumn(nullable: false)]
    private Currency $currency;

    #[
        ORM\ManyToOne(
            targetEntity: \App\Entity\Position::class,
            inversedBy: 'payments'
        )
    ]
    #[ORM\JoinColumn(nullable: false)]
    private Position $position;

    #[Groups(['payment:read', 'payment:write', 'position:read:item'])]
    #[ORM\Column(type: 'float', nullable: false, options: ['default' => 0])]
    private float $amount = 0.0;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[Groups(['payment:read', 'payment:write', 'position:read:item'])]
    #[ORM\Column(type: 'float', nullable: false, options: ['default' => 0])]
    private float $taxWithold = 0.0;

    #[Groups(['payment:read', 'payment:write', 'position:read:item'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $taxCurrency;

    #[Groups(['payment:read', 'payment:write', 'position:read:item'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $dividendType = Calendar::REGULAR;

    #[Groups(['payment:read', 'payment:write', 'position:read:item'])]
    #[ORM\Column(type: 'float', nullable: false, options: ['default' => 0])]
    private float $dividendPaid = 0.0;

    #[Groups(['payment:read', 'payment:write', 'position:read:item'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $dividendPaidCurrency = null;

    #[Groups(['payment:read', 'payment:write', 'position:read:item'])]
    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $uuid = null;

    #[Groups(['payment:read', 'payment:write', 'position:read:item'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $importfile = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $mdHash = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->setUpdatedAtValue();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

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
        if ($payDate instanceof \DateTime) {
            $this->payDate = $payDate;
        }
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
        $this->taxes =
            ($this->dividend / (100 - Constants::TAX)) * Constants::TAX;

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

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
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

    public function getMdHash(): ?string
    {
        return $this->mdHash;
    }

    public function setMdHash(string $mdHash): static
    {
        $this->mdHash = $mdHash;

        return $this;
    }
}
