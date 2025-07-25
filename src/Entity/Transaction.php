<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use DateTimeInterface;
use DateTime;
use RuntimeException;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['transaction:read', 'transaction:read:item']],
    denormalizationContext: ['groups' => ['transaction:write']],
    security: 'is_granted("ROLE_USER")',
    description: 'All the transactions made by this user for a position',
    operations: [
        new Post(),
        new Get(),
        new GetCollection()
    ]
)]
#[ORM\Table]
#[ORM\Index(columns: ['meta', 'transaction_date'])]
#[ORM\Entity(repositoryClass: 'App\Repository\TransactionRepository')]
#[ORM\HasLifecycleCallbacks]
class Transaction
{
    public const BUY = 1;
    public const SELL = 2;
    public const AMOUNT_DIGITS = 7;
    public const AMOUNT_MULTIPLE = 10000000;
    public const MANUAL_ENTRY = 'Manuel entry';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\Column(type: Types::GUID, nullable: true)]
    private ?string $uuid = null;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $side = 1;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private ?float $price = 0.0;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Currency')]
    #[ORM\JoinColumn(nullable: false)]
    private $currency;

    /**
     * @see https://github.com/doctrine/dbal/issues/3690
     * 32 bit system sets amount to string for bigint and that will mess up strong typing and will give a useless 500 error page.
     * @var float
     */
    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private float $amount = 0.0;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\Column(type: 'datetime', name: 'transaction_date')]
    private DateTime $transactionDate;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private float $profit = 0.0;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private float $allocation = 0.0;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Currency')]
    private $allocationCurrency;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Position', inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: true)]
    private $position;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private float $avgprice = 0.0;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $jobid = '';

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private float $exchangeRate = 1.0;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $meta = '';

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $importfile = '';

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private float $fx_fee = 0.0;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private float $originalPrice = 0.0;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $originalPriceCurrency = '';

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private float $stampduty = 0.0;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private float $transactionFee = 0.0;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private float $finraFee = 0.0;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private float $total = 0.0;

    #[ORM\ManyToOne(targetEntity: Pie::class, inversedBy: 'transactions')]
    private ?Pie $pie = null;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\ManyToOne]
    private ?Currency $currencyOriginalPrice = null;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\ManyToOne]
    private ?Currency $totalCurrency = null;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if (empty($this->getOriginalPrice()) && empty($this->getTotal())) {
            $context->buildViolation('Original Price and/or total should be filled!')
                ->atPath('originalPrice')
                ->addViolation();
        }

        if (empty($this->total) || $this->total == 0) {
            $context->buildViolation('Total can not be empty or zero!')
                ->atPath('total')
                ->addViolation();
        }
    }

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
    public function getSide(): ?int
    {
        return $this->side;
    }

    public function setSide(int $side): self
    {
        if (!in_array($side, [1, 2])) {
            throw new \OutOfBoundsException('Value should be 1 for buy or 2 for sell.');
        }
        $this->side = $side;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
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

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getTransactionDate(): ?DateTimeInterface
    {
        return $this->transactionDate;
    }

    public function setTransactionDate(DateTimeInterface $transactionDate): self
    {
        if (!$transactionDate instanceof DateTime) {
            throw new \RuntimeException("Transaction date is not of class DateTime");
        }
        $this->transactionDate = $transactionDate;

        return $this;
    }

    public function getProfit(): float
    {
        return $this->profit ?: 0;
    }

    public function setProfit(float $profit = 0.0): self
    {
        $this->profit = $profit;
        return $this;
    }

    public function getAllocated(): float
    {
        return $this->getAllocation();
        //return $this->getAmount() * $this->getPrice();
    }

    /**
     * Allocation is not precise enough. All will be converted to EURO if it is not already in euro.
     * Note: if chosen currency is USD then the conversion should be EURO to Dollar.
     */
    public function getAllocation(): ?float
    {
        //return ($this->originalPrice * $this->amount) / $this->exchangeRate;

        return $this->allocation;
    }

    public function setAllocation(?float $allocation): self
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

    public function getPosition(): ?Position
    {
        return $this->position;
    }

    public function setPosition(?Position $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getAvgprice(): float
    {
        return $this->avgprice ?: 0;
    }

    public function setAvgprice(float $avgprice = 0.0): self
    {
        $this->avgprice = $avgprice;

        return $this;
    }

    public function getJobid(): string
    {
        return $this->jobid ?: '';
    }

    public function setJobid(?string $jobid = ''): self
    {
        $this->jobid = $jobid;

        return $this;
    }

    public function getExchangeRate(): ?float
    {
        return (float) $this->exchangeRate;
    }

    public function setExchangeRate(?float $exchangeRate): self
    {
        $this->exchangeRate = $exchangeRate;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
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

    public function getFxFee(): float
    {
        return $this->fx_fee;
    }

    public function setFxFee(float $fx_fee): self
    {
        $this->fx_fee = $fx_fee;

        return $this;
    }

    public function getOriginalPrice(): float
    {
        return $this->originalPrice;
    }

    public function setOriginalPrice(float $originalPrice): self
    {
        $this->originalPrice = $originalPrice;

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

    public function getStampduty(): float
    {
        return $this->stampduty;
    }

    public function setStampduty(float $stampduty): self
    {
        $this->stampduty = $stampduty;

        return $this;
    }

    public function getTransactionFee(): float
    {
        return $this->transactionFee;
    }

    public function setTransactionFee(float $transactionFee): self
    {
        $this->transactionFee = $transactionFee;

        return $this;
    }

    public function getFinraFee(): float
    {
        return $this->finraFee;
    }

    public function setFinraFee(float $finraFee): self
    {
        $this->finraFee = $finraFee;

        return $this;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function setTotal(float $total): self
    {
        $this->total = $total;

        //$totalCosts = $this->fx_fee + $this->stampduty + $this->transactionFee + $this->finraFee;
        //$this->allocation = $this->total - $totalCosts;

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

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(?string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function calcAllocation(): void
    {
        if ($this->total == 0.0) {
            throw new RuntimeException("Only use this function when total has been set.");
        }

        $totalCosts = $this->fx_fee + $this->stampduty + $this->transactionFee + $this->finraFee;
        $this->allocation = $this->total - $totalCosts;
    }

    public function calcPrice(): void
    {
        if ($this->originalPrice == 0.0 || $this->exchangeRate == 0.0) {
            throw new RuntimeException("Only call this function when both original price and exchange rate are set");
        }

        $this->price = round($this->originalPrice / $this->exchangeRate, 3);
    }

    public function getCurrencyOriginalPrice(): ?Currency
    {
        return $this->currencyOriginalPrice;
    }

    public function setCurrencyOriginalPrice(?Currency $currencyOriginalPrice): static
    {
        $this->currencyOriginalPrice = $currencyOriginalPrice;

        return $this;
    }

    public function getTotalCurrency(): ?Currency
    {
        return $this->totalCurrency;
    }

    public function setTotalCurrency(?Currency $totalCurrency): static
    {
        $this->totalCurrency = $totalCurrency;

        return $this;
    }
}
