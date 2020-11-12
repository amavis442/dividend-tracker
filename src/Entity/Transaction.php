<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TransactionRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Transaction
{
    public const BROKERS = ['eToro', 'Trading212', 'Flatex'];
    public const BUY = 1;
    public const SELL = 2;
    public const AMOUNT_DIGITS = 7;
    public const AMOUNT_MULTIPLE = 10000000;

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
     * @ORM\Column(type="bigint")
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
     * @ORM\Column(type="integer", nullable=true)
     */
    private $avgprice;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $jobid;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $exchangeRate;

    /**
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", name="updated_at", nullable = true)
     */
    private $updatedAt;

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

    public function getProfit(): ?int
    {
        return $this->profit;
    }

    public function setProfit(int $profit): self
    {
        $this->profit = $profit;
        return $this;
    }

    public function getAllocated(): int
    {
        return (int) round($this->getAmount() * $this->getPrice() / 1000000000);
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

    public function getPosition(): ?Position
    {
        return $this->position;
    }

    public function setPosition(?Position $position): self
    {
        $this->position = $position;

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

    public function getJobid(): ?string
    {
        return $this->jobid;
    }

    public function setJobid(?string $jobid): self
    {
        $this->jobid = $jobid;

        return $this;
    }

    public function getExchangeRate(): ?string
    {
        return $this->exchangeRate;
    }

    public function setExchangeRate(?string $exchangeRate): self
    {
        $this->exchangeRate = $exchangeRate;

        return $this;
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

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }
}
