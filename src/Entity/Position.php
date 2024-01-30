<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: 'App\Repository\PositionRepository')]
#[ORM\HasLifecycleCallbacks]
class Position
{
    public const OPEN = 1;
    public const CLOSED = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private $price = 0.0;

    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private $amount = 0.0;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Ticker', inversedBy: 'positions')]
    #[ORM\JoinColumn(nullable: false)]
    private $ticker;

    #[ORM\Column(type: 'boolean', nullable: false, options: ["default" => false])]
    private bool $closed = false;

    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private $profit = 0.0;

    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private $allocation = 0.0;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\User', inversedBy: 'positions')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Currency')]
    #[ORM\JoinColumn(nullable: false)]
    private $currency;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Currency')]
    private $allocationCurrency;

    #[ORM\OneToMany(targetEntity: 'App\Entity\Transaction', mappedBy: 'position', orphanRemoval: true, cascade: ['persist'])]
    #[ORM\OrderBy(['transactionDate' => 'DESC'])]
    private $transactions;

    #[ORM\OneToMany(targetEntity: 'App\Entity\Payment', mappedBy: 'position')]
    #[ORM\OrderBy(['payDate' => 'DESC'])]
    private $payments;

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    private $createdAt;

    #[ORM\Column(type: 'datetime', name: 'updated_at', nullable: true)]
    private $updatedAt;

    #[ORM\JoinTable(name: 'pie_position')]
    #[ORM\JoinColumn(name: 'position_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'pie_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: 'App\Entity\Pie', inversedBy: 'positions')]
    #[ORM\OrderBy(['label' => 'DESC'])]
    private $pies;

    #[ORM\Column(type: 'datetime', name: 'closed_at', nullable: true)]
    private $closedAt;

    #[ORM\Column(type: 'float', nullable: true)]
    private $dividendTreshold = 0.0;

    /**
     * What is the maximum allocation this position should be?
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private $maxAllocation = 0;

    #[ORM\Column(type: 'boolean', options: ["default" => false])]
    private bool $ignore_for_dividend = false;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->pies = new ArrayCollection();
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if (empty($this->getPrice()) && empty($this->getAllocation())) {
            $context->buildViolation('Price and/or allocation should be filled!')
                ->atPath('price')
                ->addViolation();
        }

        if ((empty($this->amount) || $this->amount == 0) && $this->closed === false) {
            $context->buildViolation('Amount can not be empty or zero!')
                ->atPath('amount')
                ->addViolation();
        }
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

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
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
        return round(($this->getAmount() * $this->getPrice()), 3);
    }

    public function getAllocation(): ?float
    {
        return $this->allocation;
    }

    public function setAllocation(float $allocation): self
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
        $currentMonth = date('m');
        return $this->getTicker()->isDividendPayMonth($currentMonth);
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

    /**
     * @return Collection|Pie[]
     */
    public function getPies(): Collection
    {
        return $this->pies;
    }

    public function addPie(Pie $pie): self
    {
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
            if ($transaction->getTransactionDate()->format('Ymd') < $timestamp) {
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
        $this->closedAt = $closedAt;

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
}
