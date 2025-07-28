<?php

namespace App\Entity;

use App\Repository\Trading212PieMetaDataRepository;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
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

	#[
		ORM\Column(
			type: 'decimal',
			precision: 20,
			scale: 8,
			nullable: false,
			options: ['default' => '0.00000000']
		)
	]
	private string $priceAvgInvestedValue = '0.00000000';

	#[
		ORM\Column(
			type: 'decimal',
			precision: 20,
			scale: 8,
			nullable: false,
			options: ['default' => '0.00000000']
		)
	]
	private string $priceAvgValue = '0.00000000';

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
	#[ORM\OrderBy(['tickerName' => 'ASC'])]
	private Collection $trading212PieInstruments;

	#[ORM\Column(length: 255, nullable: true)]
	private ?string $pieName = null;

	#[ORM\ManyToOne(inversedBy: 'trading212PieMetaData')]
	private ?Pie $pie = null;

	#[
		ORM\Column(
			type: 'decimal',
			precision: 20,
			scale: 8,
			nullable: false,
			options: ['default' => '0.00000000']
		)
	]
	private string $gained = '0.00000000';

	#[ORM\Column(nullable: true)]
	private string $reinvested = '0.00000000';

	#[
		ORM\Column(
			type: 'decimal',
			precision: 20,
			scale: 8,
			nullable: false,
			options: ['default' => '0.00000000']
		)
	]
	private string $inCash = '0.00000000';

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

	public function getPriceAvgInvestedValue(): float
	{
		return (float) $this->priceAvgInvestedValue;
	}

	public function setPriceAvgInvestedValue(
		float $priceAvgInvestedValue
	): static {
		$this->priceAvgInvestedValue = number_format(
			$priceAvgInvestedValue,
			8,
			'.',
			''
		);

		return $this;
	}

	public function getPriceAvgValue(): float
	{
		return (float) $this->priceAvgValue;
	}

	public function setPriceAvgValue(float $priceAvgValue): static
	{
		$this->priceAvgValue = number_format($priceAvgValue, 8, '.', '');

		return $this;
	}

	public function getDiffValue(): float
	{
		$avgValue = BigDecimal::of($this->priceAvgValue);
		$invested = BigDecimal::of($this->priceAvgInvestedValue);

		$diff = $avgValue->minus($invested);

		return $diff->toScale(8, RoundingMode::HALF_UP)->toFloat();

		//return $this->priceAvgValue - $this->priceAvgInvestedValue;
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

	public function getGained(): float
	{
		return (float) $this->gained;
	}

	public function setGained(float $gained): static
	{
		$this->gained = number_format($gained, 8, '.', '');

		return $this;
	}

	public function getGainedPercentage(): float
	{
		$priceAvgInvestedValue = BigDecimal::of($this->priceAvgInvestedValue);
		$gained = BigDecimal::of($this->gained);

		$percentage = $gained
			->dividedBy($priceAvgInvestedValue, 8, RoundingMode::HALF_UP)
			->multipliedBy(BigDecimal::of('100'));

		return $percentage->toScale(2, RoundingMode::HALF_UP)->toFloat();

		/*
		return $this->priceAvgInvestedValue > 0
			? ($this->gained / $this->priceAvgInvestedValue) * 100
			: 0.0;
		*/
	}

	public function getReinvested(): float
	{
		return (float) $this->reinvested;
	}

	public function setReinvested(float $reinvested): static
	{
		$this->reinvested = number_format($reinvested, 8, '.', '');

		return $this;
	}

	public function getInCash(): float
	{
		return (float) $this->inCash;
	}

	public function setInCash(float $inCash): static
	{
		$this->inCash = number_format($inCash, 8, '.', '');

		return $this;
	}

	public function getTotalReturn(): float
	{
		$avgValue = BigDecimal::of($this->priceAvgValue);
		$gained = BigDecimal::of($this->gained);
		$invested = BigDecimal::of($this->priceAvgInvestedValue);

		$totalReturn = $avgValue->plus($gained)->minus($invested);

		return $totalReturn->toScale(8, RoundingMode::HALF_UP)->toFloat();
	}

	// This also needs to use Brick\Math like getTotalReturn
	public function getTotalReturnPercentage(): float
	{
		$priceAvgValue = BigDecimal::of($this->priceAvgValue);
		$priceAvgInvestedValue = BigDecimal::of($this->priceAvgInvestedValue);
		$gained = BigDecimal::of($this->gained);

		// Total return = (ending value + dividends) - initial investment
		$totalReturn = $priceAvgValue
			->plus($gained)
			->minus($priceAvgInvestedValue);

		if ($priceAvgInvestedValue->isZero()) {
			return 0.0; // Or throw an exception if zero investment should be invalid
		}

		$percentage = $totalReturn
			->dividedBy($priceAvgInvestedValue, 8, RoundingMode::HALF_UP)
			->multipliedBy(BigDecimal::of('100'));

		return $percentage->toScale(2, RoundingMode::HALF_UP)->toFloat();

		/*
		return $this->priceAvgInvestedValue > 0
			? ($this->getTotalReturn() / $this->priceAvgInvestedValue) * 100
			: 0.0;
		*/
	}
}
