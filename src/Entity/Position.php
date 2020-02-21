<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
     * @ORM\Column(type="integer", nullable=true)
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
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $closed;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $profit;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $allocation;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency")
     */
    private $allocationCurrency;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Transaction", mappedBy="position", cascade={"persist"})
     * ORM\OrderBy({"transactionDate" = "ASC"})
     */
    private $transactions;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if (empty($this->getPrice()) && empty($this->getAllocation())) {
            $context->buildViolation('Price and/or allocation should be filled!')
                ->atPath('price')
                ->addViolation();
        }

        if (empty($this->amount) || $this->amount === 0) {
            $context->buildViolation('Amount can not be empty or zero!')
                ->atPath('amount')
                ->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(?int $price): self
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

    public function getClosed(): ?bool
    {
        return $this->closed;
    }

    public function setClosed(?bool $closed): self
    {
        $this->closed = $closed;
        return $this;
    }

    public function getProfit(): ?float
    {
        return $this->profit;
    }

    public function setProfit(int $profit): self
    {
        $this->profit = $profit;
        return $this;
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
}
