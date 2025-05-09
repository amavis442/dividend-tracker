<?php

namespace App\Entity;

use App\Repository\Trading212PieInstrumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: Trading212PieInstrumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Trading212PieInstrument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $tickerName = null;

    #[ORM\Column]
    private ?float $ownedQuantity = null;

    #[ORM\Column]
    private ?float $priceAvgInvestedValue = null;

    #[ORM\Column]
    private ?float $priceAvgValue = null;

    #[ORM\Column]
    private ?float $priceAvgResult = null;

    #[ORM\Column]
    private array $raw = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?int $trading212PieId = null;

    #[ORM\ManyToOne(inversedBy: 'trading212PieInstruments')]
    private ?Trading212PieMetaData $trading212PieMetaData = null;

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

    public function getTickerName(): ?string
    {
        return $this->tickerName;
    }

    public function setTickerName(string $tickerName): static
    {
        $this->tickerName = $tickerName;

        return $this;
    }

    public function getOwnedQuantity(): ?float
    {
        return $this->ownedQuantity;
    }

    public function setOwnedQuantity(float $ownedQuantity): static
    {
        $this->ownedQuantity = $ownedQuantity;

        return $this;
    }

    public function getPriceAvgInvestedValue(): ?float
    {
        return $this->priceAvgInvestedValue;
    }

    public function setPriceAvgInvestedValue(float $priceAvgInvestedValue): static
    {
        $this->priceAvgInvestedValue = $priceAvgInvestedValue;

        return $this;
    }

    public function getPriceAvgValue(): ?float
    {
        return $this->priceAvgValue;
    }

    public function setPriceAvgValue(float $priceAvgValue): static
    {
        $this->priceAvgValue = $priceAvgValue;

        return $this;
    }

    public function getPriceAvgResult(): ?float
    {
        return $this->priceAvgResult;
    }

    public function setPriceAvgResult(float $priceAvgResult): static
    {
        $this->priceAvgResult = $priceAvgResult;

        return $this;
    }

    public function getRaw(): array
    {
        return $this->raw;
    }

    public function setRaw(array $raw): static
    {
        $this->raw = $raw;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getTrading212PieId(): ?int
    {
        return $this->trading212PieId;
    }

    public function setTrading212PieId(int $trading212PieId): static
    {
        $this->trading212PieId = $trading212PieId;

        return $this;
    }

    public function getTrading212PieMetaData(): ?Trading212PieMetaData
    {
        return $this->trading212PieMetaData;
    }

    public function setTrading212PieMetaData(?Trading212PieMetaData $trading212PieMetaData): static
    {
        $this->trading212PieMetaData = $trading212PieMetaData;

        return $this;
    }
}
