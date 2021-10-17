<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use DateTimeInterface;
use DateTime;

//use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TransactionRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(indexes={@ORM\Index(columns={"meta","transaction_date"})})
 */
class Transaction
{
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $meta;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $importfile;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fx_fee;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $originalPrice;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $originalPriceCurrency;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $stampduty;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $transactionFee;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $finraFee;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $total;

    /**
     * @ORM\ManyToOne(targetEntity=Pie::class, inversedBy="transactions")
     */
    private $pie;

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

    public function getPrice(): ?float
    {
        return $this->price / Constants::VALUTA_PRECISION;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price * Constants::VALUTA_PRECISION;

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

    public function getAmount(): ?string
    {
        return $this->amount / Constants::AMOUNT_PRECISION;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount * Constants::AMOUNT_PRECISION;

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
        return ($this->profit / Constants::VALUTA_PRECISION) ?: null;
    }

    public function setProfit(float $profit): self
    {
        $this->profit = $profit * Constants::VALUTA_PRECISION;
        return $this;
    }

    public function getAllocated(): float
    {
        return $this->getAmount() * $this->getPrice();
    }

    public function getAllocation(): ?float
    {
        return $this->allocation / Constants::VALUTA_PRECISION;
    }

    public function setAllocation(?float $allocation): self
    {
        $this->allocation = $allocation * Constants::VALUTA_PRECISION;
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

    public function getPosition(): ?Position
    {
        return $this->position;
    }

    public function setPosition(?Position $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getAvgprice(): ?float
    {
        return $this->avgprice / Constants::VALUTA_PRECISION;
    }

    public function setAvgprice(?float $avgprice): self
    {
        $this->avgprice = $avgprice * Constants::VALUTA_PRECISION;

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

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getMeta(): ?string
    {
        return $this->meta;
    }

    public function setMeta(?string $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    public function getImportfile(): ?string
    {
        return $this->importfile;
    }

    public function setImportfile(?string $importfile): self
    {
        $this->importfile = $importfile;

        return $this;
    }

    public function getFxFee(): ?float
    {
        return $this->fx_fee / Constants::VALUTA_PRECISION;
    }

    public function setFxFee(?float $fx_fee): self
    {
        $this->fx_fee = $fx_fee * Constants::VALUTA_PRECISION;

        return $this;
    }

    public function getOriginalPrice(): ?float
    {
        return $this->originalPrice / Constants::VALUTA_PRECISION;
    }

    public function setOriginalPrice(?float $originalPrice): self
    {
        $this->originalPrice = $originalPrice * Constants::VALUTA_PRECISION;

        return $this;
    }

    public function getOriginalPriceCurrency(): ?string
    {
        return $this->originalPriceCurrency;
    }

    public function setOriginalPriceCurrency(?string $originalPriceCurrency): self
    {
        $this->originalPriceCurrency = $originalPriceCurrency;

        return $this;
    }

    public function getStampduty(): ?float
    {
        return $this->stampduty / Constants::VALUTA_PRECISION;
    }

    public function setStampduty(?float $stampduty): self
    {
        $this->stampduty = $stampduty * Constants::VALUTA_PRECISION;

        return $this;
    }

    public function getTransactionFee(): ?float
    {
        return $this->transactionFee / Constants::VALUTA_PRECISION;
    }

    public function setTransactionFee(?float $transactionFee): self
    {
        $this->transactionFee = $transactionFee * Constants::VALUTA_PRECISION;

        return $this;
    }

    public function getFinraFee(): ?float
    {
        return $this->finraFee / Constants::VALUTA_PRECISION;
    }

    public function setFinraFee(?float $finraFee): self
    {
        $this->finraFee = $finraFee * Constants::VALUTA_PRECISION;

        return $this;
    }

    public function getTotal(): ?float
    {
        return $this->total / Constants::VALUTA_PRECISION;
    }

    public function setTotal(float $total): self
    {
        $this->total = $total * Constants::VALUTA_PRECISION;

        return $this;
    }

    public function getPie(): ?Pie
    {
        return $this->pie;
    }

    public function setPie(?Pie $pie): self
    {
        $this->pie = $pie;

        return $this;
    }

    public function netOrderValue(): float
    {
        return $this->getAllocation() + $this->getFinraFee() + $this->getStampduty() + $this->getFxFee() + $this->getTransactionFee();
    }
}
