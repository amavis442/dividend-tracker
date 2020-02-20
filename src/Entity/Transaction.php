<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Exception;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TransactionRepository")
 */
class Transaction
{
    public const BROKERS = ['eToro', 'Trading212', 'Flatex'];
    public const BUY = 1;
    public const SELL = 2;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", options={"default" : 1})
     */
    private $side;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $price;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency")
     * @ORM\JoinColumn(nullable=false)
     */
    private $currency;

    /**
     * @ORM\Column(type="integer")
     */
    private $amount;

    /**
     * @ORM\Column(type="datetime", name="transaction_date")
     */
    private $transactionDate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $profit;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $allocation;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency")
     */
    private $allocationCurrency;

    /**
     * @ORM\Column(type="string", length=255,  options={"default" : "Trading212"})
     */
    private $broker;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Position", inversedBy="transactions")
     * @ORM\JoinColumn(nullable=true)
     */
    private $position;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="positions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Ticker", inversedBy="transactions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $ticker;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $avgprice;

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
    public function getSide(): ?int
    {
        return $this->side;
    }

    public function setSide(int $side): self
    {
        if (!in_array($side, [1, 2])) {
            throw new Exception('Value should be 1 for buy or 2 for sell.');
        }
        $this->side = $side;

        return $this;
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

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

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

    public function getTransactionDate(): ?\DateTimeInterface
    {
        return $this->transactionDate;
    }

    public function setTransactionDate(\DateTimeInterface $transactionDate): self
    {
        $this->transactionDate = $transactionDate;

        return $this;
    }

    public function getProfit(): ?float
    {
        return $this->profit / 100;
    }

    public function setProfit(int $profit): self
    {
        $this->profit = $profit;
        return $this;
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

    public function getAllocationCurrency(): ?Currency
    {
        return $this->allocationCurrency;
    }

    public function setAllocationCurrency(?Currency $allocationCurrency): self
    {
        $this->allocationCurrency = $allocationCurrency;

        return $this;
    }

    public function getBroker(): ?string
    {
        return $this->broker;
    }

    public function setBroker(string $broker): self
    {
        $this->broker = $broker;

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

    public function getPosition(): ?Position
    {
        return $this->position;
    }

    public function setPosition(?Position $position): self
    {
        $this->position = $position;

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

    public function getAvgprice(): ?int
    {
        return $this->avgprice;
    }

    public function setAvgprice(?int $avgprice): self
    {
        $this->avgprice = $avgprice;

        return $this;
    }
}
