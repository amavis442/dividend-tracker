<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['calendar:read', 'calendar:read:item']],
    denormalizationContext: ['groups' => ['calendar:write']],
    security: 'is_granted("ROLE_USER")',
    operations: [
        new Get(),
        new GetCollection()
    ]
)]
#[ORM\Entity(repositoryClass: 'App\Repository\CalendarRepository')]
#[ORM\HasLifecycleCallbacks]
class Calendar
{
    public const SOURCE_SCRIPT = 'script';
    public const SOURCE_MANUEL = 'manual';
    public const REGULAR = 'Regular';
    public const SUPPLEMENT = 'Supplement';
    public const SPECIAL = 'Special';


    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Ticker', inversedBy: 'calendars')]
    #[ORM\JoinColumn(nullable: false)]
    private $ticker;

    #[Groups(['calendar:read', 'calendar:write', 'ticker:read:item', 'position:read:item', 'transaction:read'])]
    #[ORM\Column(type: 'date', name: 'ex_dividend_date')]
    private $exDividendDate;

    #[Groups(['calendar:read', 'calendar:write', 'ticker:read:item', 'position:read:item', 'transaction:read'])]
    #[ORM\Column(type: 'date', name: 'record_date')]
    private $recordDate;

    #[Groups(['calendar:read', 'calendar:write', 'ticker:read:item', 'position:read:item', 'transaction:read'])]
    #[ORM\Column(type: 'date', name: 'payment_date')]
    private $paymentDate;

    #[Groups(['calendar:read', 'calendar:write', 'ticker:read:item', 'position:read:item', 'transaction:read'])]
    #[ORM\Column(
        type: 'float',
        name: 'cash_amount',
        nullable: false,
        options: ["default" => 0]
    )]
    private $cashAmount = 0.0;

    #[ORM\OneToMany(targetEntity: 'App\Entity\Payment', mappedBy: 'calendar')]
    #[ORM\JoinColumn(nullable: true)]
    #[ORM\OrderBy(['payDate' => 'DESC'])]
    private $payments;

    #[Groups(['calendar:read', 'calendar:write', 'ticker:read:item', 'position:read:item', 'transaction:read'])]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Currency')]
    private $currency;

    #[Groups(['calendar:read', 'calendar:write', 'ticker:read:item', 'position:read:item', 'transaction:read'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $dividendType;

    /**
     * Was this added manual or script?
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $source;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $description;

    #[ORM\Column()]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * Gets triggered only on insert
     */
    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->setUpdatedAtValue();
    }

    /**
     * Gets triggered every time on update
     */
    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __construct()
    {
        $this->payments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getExDividendDate(): ?\DateTimeInterface
    {
        return $this->exDividendDate;
    }

    public function setExDividendDate(\DateTimeInterface $exDividendDate): self
    {
        $this->exDividendDate = $exDividendDate;
        $this->recordDate = $exDividendDate;
        return $this;
    }

    public function getRecordDate(): ?\DateTimeInterface
    {
        return $this->recordDate;
    }

    public function setRecordDate(\DateTimeInterface $recordDate): self
    {
        $this->recordDate = $recordDate;

        return $this;
    }

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(\DateTimeInterface $paymentDate): self
    {
        $this->paymentDate = $paymentDate;

        return $this;
    }

    public function getCashAmount(): ?float
    {
        return $this->cashAmount;
    }

    public function getNetCashAmount(): ?float
    {
        return ($this->cashAmount * (1 - (Constants::TAX / 100)) / Constants::EXCHANGE);
    }

    public function setCashAmount(float $cashAmount): self
    {
        $this->cashAmount = number_format($cashAmount, 3);

        return $this;
    }

    public function getDaysLeft(): ?int
    {
        $current = new DateTime();
        if ($this->paymentDate instanceof DateTime) {
            if ($current->format('Ymd') > $this->paymentDate->format('Ymd')) {
                return null;
            }
            if ($current->format('Ymd') === $this->paymentDate->format('Ymd')) {
                return 0;
            }
            return (int) $current->diff($this->paymentDate)->format('%a') + 1;
        }
        return null;
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
            $payment->setCalendar($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        if ($this->payments->contains($payment)) {
            $this->payments->removeElement($payment);
            // set the owning side to null (unless already changed)
            if ($payment->getCalendar() === $this) {
                $payment->setCalendar(null);
            }
        }

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

    public function getDividendType(): ?string
    {
        return $this->dividendType ?? self::REGULAR;
    }

    public function setDividendType(?string $dividendType): self
    {
        $this->dividendType = $dividendType;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
}
