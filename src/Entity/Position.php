<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[
    ApiResource(
        normalizationContext: [
            'groups' => ['position:read', 'position:read:item'],
        ],
        denormalizationContext: ['groups' => ['position:write']],
        security: 'is_granted("ROLE_USER")',
        operations: [new Get(), new GetCollection()]
    )
]
#[ORM\Entity(repositoryClass: 'App\Repository\PositionRepository')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'position_closed_idx', fields: ['closed'])]
class Position
{
    public const OPEN = 1;
    public const CLOSED = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * Average price
     */
    #[Groups(['position:read', 'position:write', 'ticker:read:item'])]
    #[ORM\Column(type: 'float', nullable: false, options: ['default' => 0])]
    private float $price = 0.0;

    #[Groups(['position:read', 'position:write', 'ticker:read:item'])]
    #[ORM\Column(type: 'float', nullable: false, options: ['default' => 0])]
    private float $amount = 0.0;

    #[Groups(['position:read', 'position:write'])]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Ticker', inversedBy: 'positions')]
    #[ORM\JoinColumn(nullable: false)]
    private Ticker $ticker;

    #[Groups(['position:read', 'position:write', 'ticker:read:item'])]
    #[
        ORM\Column(
            type: 'boolean',
            nullable: false,
            options: ['default' => false]
        )
    ]
    private bool $closed = false;

    #[Groups(['position:read', 'position:write', 'ticker:read:item'])]
    #[ORM\Column(type: 'float', nullable: false, options: ['default' => 0])]
    private float $profit = 0.0;

    #[Groups(['position:read', 'position:write', 'ticker:read:item'])]
    #[ORM\Column(type: 'float', nullable: false, options: ['default' => 0])]
    private float $allocation = 0.0;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\User', inversedBy: 'positions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[Groups(['position:read', 'position:write', 'ticker:read:item'])]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Currency')]
    #[ORM\JoinColumn(nullable: false)]
    private Currency $currency;

    #[Groups(['position:read', 'position:write', 'ticker:read:item'])]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Currency')]
    private ?Currency $allocationCurrency;

    #[Groups(['position:read', 'position:write'])]
    #[
        ORM\OneToMany(
            targetEntity: 'App\Entity\Transaction',
            mappedBy: 'position',
            orphanRemoval: true,
            cascade: ['persist']
        )
    ]
    #[ORM\OrderBy(['transactionDate' => 'DESC'])]
    private Collection $transactions;

    #[Groups(['position:read', 'position:write'])]
    #[ORM\OneToMany(targetEntity: \App\Entity\Payment::class, mappedBy: 'position')]
    #[ORM\OrderBy(['payDate' => 'DESC'])]
    private Collection $payments;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[Groups(['position:read', 'position:write'])]
    #[ORM\JoinTable(name: 'pie_position')]
    #[ORM\JoinColumn(name: 'position_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'pie_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: 'App\Entity\Pie', inversedBy: 'positions')]
    #[ORM\OrderBy(['label' => 'DESC'])]
    private Collection $pies;

    #[Groups(['position:read', 'position:write'])]
    #[ORM\Column(type: 'datetime', name: 'closed_at', nullable: true)]
    private ?DateTime $closedAt;

    #[Groups(['position:read', 'position:write', 'ticker:read:item'])]
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $dividendTreshold = 0.0;

    /**
     * What is the maximum allocation this position should be?
     */
    #[Groups(['position:read', 'position:write', 'ticker:read:item'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maxAllocation = 0;

    #[Groups(['position:read', 'position:write', 'ticker:read:item'])]
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $ignore_for_dividend = false;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $uuid = null;

    #[ORM\ManyToOne(inversedBy: 'positions')]
    private ?Portfolio $portfolio = null;

    private float $percentageAllocation = 0.0;
    private int $payoutFrequency = 3;
    private bool $divDate = false;
    private float $cashAmount = 0.0;
    private ?Currency $cashCurrency = null;
    private float $forwardNetDividend = 0.0;
    private float $forwardNetDividendYield = 0.0;
    private float $forwardNetDividendYieldPerShare = 0.0;
    private float $netDividendPerShare = 0.0;
    private ?DateTime $exDividendDate = null;
    private ?DateTime $paymentDate = null;
    private bool $isMaxAllocation = false;
    private ?Collection $dividendCalendars = null;
    private float $dividend = 0.0;


    #[ORM\Column(nullable: true)]
    private ?float $adjustedAmount = null;

    #[ORM\Column(nullable: true)]
    private ?float $adjustedAveragePrice = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $adjustedMetricsLastUpdatedAt = null;



    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->setUpdatedAtValue();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        // Needed because of late implementation off uuid and
        // do not want to generate it all at once.
        if (!$this->getUuid()) {
            $uuid = Uuid::v4();
            $this->setUuid($uuid);
        }

        $this->updatedAt = new \DateTimeImmutable();
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if (empty($this->getPrice()) && empty($this->getAllocation())) {
            $context
                ->buildViolation('Price and/or allocation should be filled!')
                ->atPath('price')
                ->addViolation();
        }

        if (
            (empty($this->amount) || $this->amount == 0) &&
            $this->closed === false
        ) {
            $context
                ->buildViolation('Amount can not be empty or zero!')
                ->atPath('amount')
                ->addViolation();
        }
    }

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->pies = new ArrayCollection();
        $this->dividendCalendars = new ArrayCollection();
        $this->isMaxAllocation = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount ?? 0.0;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getTicker(): Ticker
    {
        return $this->ticker;
    }

    public function setTicker(Ticker $ticker): self
    {
        $this->ticker = $ticker;

        return $this;
    }

    public function getClosed(): bool
    {
        return $this->closed;
    }

    public function setClosed(bool $closed): self
    {
        $this->closed = $closed;

        return $this;
    }

    public function getProfit(): float
    {
        return $this->profit;
    }

    public function setProfit(float $profit): self
    {
        $this->profit = $profit;

        return $this;
    }

    public function getAllocated(): float
    {
        return round((float) $this->getAmount() * $this->getPrice(), 3);
    }

    public function getAllocation(): float
    {
        return $this->allocation ?? 0.0;
    }

    public function setAllocation(float $allocation): self
    {
        $this->allocation = $allocation;
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

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function setCurrency(Currency $currency): self
    {
        $this->currency = $currency;

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
            $transaction->setPosition($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        if ($this->transactions->contains($transaction)) {
            $this->transactions->removeElement($transaction);
            // set the owning side to null (unless already changed)
            if ($transaction->getPosition() === $this) {
                $transaction->setPosition(null);
            }
        }

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
        }

        return $this;
    }

    public function isDividendPayMonth(): bool
    {
        $currentMonth = (int) date('m');
        return $this->getTicker()->isDividendPayMonth($currentMonth);
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection|Pie[]
     */
    public function getPies(): ?Collection
    {
        return $this->pies;
    }

    public function addPie(Pie $pie): self
    {
        if ($this->pies == null) {
            $this->pies = new ArrayCollection();
        }

        if (!$this->pies->contains($pie)) {
            $this->pies[] = $pie;
            $pie->addPosition($this);
        }

        return $this;
    }

    public function removePie(Pie $pie): self
    {
        if ($this->pies->removeElement($pie)) {
            $pie->removePosition($this);
        }

        return $this;
    }

    public function hasPie(): bool
    {
        if (count($this->pies) > 0) {
            return true;
        }

        return false;
    }

    public function getAmountPerDate(DateTimeInterface $datetime): ?float
    {
        $timestamp = $datetime->format('Ymd');
        $amount = 0.0;
        foreach ($this->getTransactions() as $transaction) {
            if (
                $transaction->getTransactionDate()->format('Ymd') < $timestamp
            ) {
                if ($transaction->getSide() == Transaction::BUY) {
                    $amount += $transaction->getAmount();
                }
                if ($transaction->getSide() == Transaction::SELL) {
                    $amount -= $transaction->getAmount();
                }
            }
        }

        return $amount;
    }

    public function getClosedAt(): ?\DateTimeInterface
    {
        return $this->closedAt;
    }

    public function setClosedAt(?\DateTimeInterface $closedAt): self
    {
        $this->closedAt =
            $closedAt instanceof DateTime ? $closedAt : new DateTime('now');

        return $this;
    }

    public function getDividendTreshold(): ?float
    {
        return $this->dividendTreshold;
    }

    public function setDividendTreshold(?float $dividendTreshold): self
    {
        $this->dividendTreshold = $dividendTreshold;

        return $this;
    }

    public function getMaxAllocation(): ?int
    {
        return $this->maxAllocation;
    }

    public function setMaxAllocation(?int $maxAllocation): self
    {
        $this->maxAllocation = $maxAllocation;

        return $this;
    }

    public function isIgnoreForDividend(): ?bool
    {
        return $this->ignore_for_dividend;
    }

    public function setIgnoreForDividend(?bool $ignore_for_dividend): self
    {
        $this->ignore_for_dividend = $ignore_for_dividend;

        return $this;
    }

    /**
     * Percentage of total investment
     */
    public function setPercentageAllocation(float $totalInvested): self
    {
        if ($totalInvested > 0) {
            $this->percentageAllocation =
                ($this->allocation / $totalInvested) * 100;
        }
        return $this;
    }

    public function getPercentageAllocation(): float
    {
        return $this->percentageAllocation;
    }

    /**
     * Frequency of dividends paid 3,6,12 etc
     */
    public function setDividendPayoutFrequency($payoutFrequency): self
    {
        $this->payoutFrequency = $payoutFrequency;

        return $this;
    }

    public function getDividendPayoutFrequency(): int
    {
        return $this->payoutFrequency;
    }

    /**
     * Get has a calendar entry
     *
     * @return  boolean
     */
    public function hasDivDate(): bool
    {
        return $this->divDate;
    }

    public function setDivDate(bool $divDate): self
    {
        $this->divDate = $divDate;

        return $this;
    }

    /**
     * @param  float  $cashAmount
     *
     * @return  self
     */
    public function setCashAmount(float $cashAmount): self
    {
        $this->cashAmount = $cashAmount;

        return $this;
    }

    /**
     * @return  float
     */
    public function getCashAmount(): float
    {
        return $this->cashAmount;
    }

    /**
     *
     * @return  Currency
     */
    public function getCashCurrency(): ?Currency
    {
        return $this->cashCurrency;
    }

    /**
     * @param  Currency  $cashCurrency
     *
     * @return  self
     */
    public function setCashCurrency(Currency $cashCurrency): self
    {
        $this->cashCurrency = $cashCurrency;

        return $this;
    }

    /**
     * @param  float  $forwardNetDividend  Undocumented variable
     *
     * @return  self
     */
    public function setForwardNetDividend(float $forwardNetDividend): self
    {
        $this->forwardNetDividend = $forwardNetDividend;

        return $this;
    }

    /**
     * @return  float
     */
    public function getForwardNetDividend(): float
    {
        return $this->forwardNetDividend;
    }

    /**
     * @param  float  $forwardNetDividendYield
     *
     * @return  self
     */
    public function setForwardNetDividendYield(
        float $forwardNetDividendYield
    ): self {
        $this->forwardNetDividendYield = $forwardNetDividendYield;

        return $this;
    }

    /**
     * @return  float
     */
    public function getForwardNetDividendYield(): float
    {
        return $this->forwardNetDividendYield;
    }

    /**
     * Get current dividend yield per share based on current marketprice
     *
     * @return  float
     */
    public function getForwardNetDividendYieldPerShare(): float
    {
        return $this->forwardNetDividendYieldPerShare ?? 0;
    }

    /**
     * Set current dividend yield per share based on current marketprice
     *
     * @param  float  $forwardNetDividendYieldPerShare  Current dividend yield per share based on current marketprice
     *
     * @return  self
     */
    public function setForwardNetDividendYieldPerShare(
        float $forwardNetDividendYieldPerShare
    ): self {
        $this->forwardNetDividendYieldPerShare = $forwardNetDividendYieldPerShare;

        return $this;
    }

    /**
     * Get net dividend per share
     *
     * @return  float
     */
    public function getNetDividendPerShare(): float
    {
        return $this->netDividendPerShare ?? 0.0;
    }

    /**
     * Set net dividend per share
     *
     * @param  null|float  $netDividendPerShare  Net dividend per share
     *
     * @return  self
     */
    public function setNetDividendPerShare(?float $netDividendPerShare): self
    {
        $this->netDividendPerShare = $netDividendPerShare ?? 0.0;

        return $this;
    }

    /**
     * @return  DateTime
     */
    public function getExDividendDate(): ?DateTime
    {
        return $this->exDividendDate;
    }

    /**
     * @param  DateTime  $exDividendDate
     *
     * @return  self
     */
    public function setExDividendDate(DateTime $exDividendDate): self
    {
        $this->exDividendDate = $exDividendDate;

        return $this;
    }

    /**
     * @return  DateTime
     */
    public function getPaymentDate(): ?DateTime
    {
        return $this->paymentDate;
    }

    /**
     * @param  DateTime  $paymentDate  Undocumented variable
     *
     * @return  self
     */
    public function setPaymentDate(DateTime $paymentDate): self
    {
        $this->paymentDate = $paymentDate;

        return $this;
    }

    /**
     * Get collection of dividend calenders of future payments
     *
     * @return  array
     */
    public function getDividendCalendars(): array
    {
        if ($this->dividendCalendars == null) {
            return [];
        }

        return array_reverse($this->dividendCalendars->toArray());
    }

    /**
     * Add future calendar to collection
     *
     * @param  Calendar  $dividendCalendar
     *
     * @return  self
     */
    public function addDividendCalendar(Calendar $dividendCalendar): self
    {
        if ($this->dividendCalendars == null) {
            $this->dividendCalendars = new ArrayCollection();
        }

        if (!$this->dividendCalendars->contains($dividendCalendar)) {
            $this->dividendCalendars->add($dividendCalendar);
        }

        return $this;
    }

    public function computeCurrentDividendDates(DateTime $currentDate): self
    {
        foreach ($this->ticker->getCalendars() as $currentCalendar) {
            if ($currentCalendar->getPaymentDate() >= $currentDate) {
                $this->addDividendCalendar($currentCalendar);
            }
        }

        return $this;
    }

    /**
     * Get has maximum allocation been reached
     *
     * @return  bool
     */
    public function getIsMaxAllocation(): bool
    {
        return $this->isMaxAllocation;
    }

    /**
     * Set has maximum allocation been reached
     *
     * @return  self
     */
    public function computeIsMaxAllocation(): self
    {
        if ($this->getMaxAllocation() !== null) {
            if ($this->getAllocation() > $this->getMaxAllocation()) {
                $this->isMaxAllocation = true;
            }
        }

        return $this;
    }

        /**
     * Get total received dividends
     *
     * @return  float
     */
    public function getDividend(): float
    {
        return $this->dividend;
    }

    /**
     * Set total received dividends
     *
     * @param  float  $dividend  Total received dividends
     *
     * @return  self
     */
    public function setDividend(float $dividend): self
    {
        $this->dividend = $dividend;

        return $this;
    }

    public function computeReceivedDividends(): float
    {
        $totalDividend = 0.0;
        foreach ($this->getPayments() as $payment) {
            $totalDividend += $payment->getDividend();
        }
        $this->setDividend($totalDividend);
        return $totalDividend;
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

    public function getPortfolio(): ?Portfolio
    {
        return $this->portfolio;
    }

    public function setPortfolio(?Portfolio $portfolio): static
    {
        $this->portfolio = $portfolio;

        return $this;
    }

    public function getAdjustedAmount(): float
    {
        return $this->adjustedAmount ?? 0.0;
    }

    public function setAdjustedAmount(?float $adjustedAmount): static
    {
        $this->adjustedAmount = $adjustedAmount;

        return $this;
    }

    public function getAdjustedAveragePrice(): float
    {
        return $this->adjustedAveragePrice ?? 0.0;
    }

    public function setAdjustedAveragePrice(?float $adjustedAveragePrice): static
    {
        $this->adjustedAveragePrice = $adjustedAveragePrice;

        return $this;
    }

    public function getAdjustedMetricsLastUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->adjustedMetricsLastUpdatedAt;
    }

    public function setAdjustedMetricsLastUpdatedAt(?\DateTimeImmutable $adjustedMetricsLastUpdatedAt): static
    {
        $this->adjustedMetricsLastUpdatedAt = $adjustedMetricsLastUpdatedAt;

        return $this;
    }
}
