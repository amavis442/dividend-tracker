<?php

namespace App\Entity;

use App\Repository\Trading212PieMetaDataRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: Trading212PieMetaDataRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Trading212PieMetaData
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column]
	private ?int $trading212PieId = null;

	#[ORM\Column]
	private ?float $priceAvgInvestedValue = null;

	#[ORM\Column]
	private ?float $priceAvgValue = null;

	#[ORM\Column]
	private array $raw = [];

	#[ORM\Column]
	private ?\DateTimeImmutable $createdAt = null;

	#[ORM\Column(nullable: true)]
	private ?\DateTimeImmutable $updatedAt = null;

	/**
	 * @var Collection<int, Trading212PieInstrument>
	 */
	#[
		ORM\OneToMany(
			targetEntity: Trading212PieInstrument::class,
			mappedBy: 'trading212PieMetaData'
		)
	]
	#[ORM\OrderBy(['tickerName'=> 'ASC'])]
	private Collection $trading212PieInstruments;

	#[ORM\Column(length: 255, nullable: true)]
	private ?string $pieName = null;

	#[ORM\ManyToOne(inversedBy: 'trading212PieMetaData')]
	private ?Pie $pie = null;

	#[ORM\Column(nullable:true)]
	private ?float $gained = 0.0;

	#[ORM\Column(nullable:true)]
	private ?float $reinvested = 0.0;

	#[ORM\Column(nullable:true)]
	private ?float $inCash = 0.0;

	public function __construct()
	{
		$this->trading212PieInstruments = new ArrayCollection();
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

	public function getTrading212PieId(): ?int
	{
		return $this->trading212PieId;
	}

	public function setTrading212PieId(int $trading212PieId): static
	{
		$this->trading212PieId = $trading212PieId;

		return $this;
	}

	public function getPriceAvgInvestedValue(): ?float
	{
		return $this->priceAvgInvestedValue;
	}

	public function setPriceAvgInvestedValue(
		float $priceAvgInvestedValue
	): static {
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

	public function getDiffValue(): float
	{
		return $this->priceAvgValue - $this->priceAvgInvestedValue;
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

	/**
	 * @return Collection<int, Trading212PieInstrument>
	 */
	public function getTrading212PieInstruments(): Collection
	{
		return $this->trading212PieInstruments;
	}

	public function addTrading212PieInstrument(
		Trading212PieInstrument $trading212PieInstrument
	): static {
		if (
			!$this->trading212PieInstruments->contains($trading212PieInstrument)
		) {
			$this->trading212PieInstruments->add($trading212PieInstrument);
			$trading212PieInstrument->setTrading212PieMetaData($this);
		}

		return $this;
	}

	public function removeTrading212PieInstrument(
		Trading212PieInstrument $trading212PieInstrument
	): static {
		if (
			$this->trading212PieInstruments->removeElement(
				$trading212PieInstrument
			)
		) {
			// set the owning side to null (unless already changed)
			if (
				$trading212PieInstrument->getTrading212PieMetaData() === $this
			) {
				$trading212PieInstrument->setTrading212PieMetaData(null);
			}
		}

		return $this;
	}

	public function getPieName(): ?string
	{
		return $this->pieName;
	}

	public function setPieName(?string $pieName): static
	{
		$this->pieName = $pieName;

		return $this;
	}

	public function getPie(): ?Pie
	{
		return $this->pie;
	}

	public function setPie(?Pie $pie): static
	{
		$this->pie = $pie;

		return $this;
	}

	public function getGained(): ?float
	{
		return $this->gained;
	}

	public function setGained(float $gained): static
	{
		$this->gained = $gained;

		return $this;
	}

	public function getGainedPercentage(): float
	{
		return ($this->gained / $this->priceAvgInvestedValue) * 100;
	}

	public function getReinvested(): ?float
	{
		return $this->reinvested;
	}

	public function setReinvested(float $reinvested): static
	{
		$this->reinvested = $reinvested;

		return $this;
	}

	public function getInCash(): ?float
	{
		return $this->inCash;
	}

	public function setInCash(float $inCash): static
	{
		$this->inCash = $inCash;

		return $this;
	}

    public function getTotalReturn(): float
    {
        return $this->priceAvgValue + $this->gained - $this->priceAvgInvestedValue;
    }

	public function getTotalReturnPercentage(): float
    {
        return ($this->getTotalReturn() / $this->priceAvgInvestedValue) * 100;
    }
}
