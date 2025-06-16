<?php

namespace App\Entity;

use App\Repository\Trading212PieInstrumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\ManyToOne(inversedBy: 'trading212PieInstruments')]
    private ?Ticker $ticker = null;

    private ?float $avgDividendPerShare = 0.0;
    private ?float $avgExpectedDividend = 0.0;
    private ?float $currentDividendPerShare = 0.0;
    private ?float $currentDividend = 0.0;
    private ?float $currentYearlyYield = 0.0;
    private ?float $avgYearlyYield = 0.0;
    private ?float $dividendPaid = 0.0;

	private ?float $taxRate = 0.15;
	private ?float $exchangeRate = 0.0;
	private array $calendars = [];
	private array $dividend = [];
	private float $monthlyYield = 0.0;
	private float $price = 0.0;
	private float $avgPrice = 0.0;

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

    public function getTicker(): ?Ticker
    {
        return $this->ticker;
    }

    public function setTicker(?Ticker $ticker): static
    {
        $this->ticker = $ticker;

        return $this;
    }

	/**
	 * Get the value of avgDividendPerShare
	 *
	 * @return  float
	 */
	public function getAvgDividendPerShare(): float
	{
		return $this->avgDividendPerShare;
	}

	/**
	 * Set the value of avgDividendPerShare
	 *
	 * @param   float  $avgDividendPerShare
	 *
	 * @return  self
	 */
	public function setAvgDividendPerShare(float $avgDividendPerShare): self
	{
		$this->avgDividendPerShare = $avgDividendPerShare;

		return $this;
	}

	/**
	 * Get the value of currentDividendPerShare
	 *
	 * @return  float
	 */
	public function getCurrentDividendPerShare(): float
	{
		return $this->currentDividendPerShare;
	}

	/**
	 * Set the value of currentDividendPerShare
	 *
	 * @param   float  $currentDividendPerShare
	 *
	 * @return  self
	 */
	public function setCurrentDividendPerShare(float $currentDividendPerShare): self
	{
		$this->currentDividendPerShare = $currentDividendPerShare;

		return $this;
	}

	/**
	 * Get the value of avgExpectedDividend
	 *
	 * @return  float
	 */
	public function getAvgExpectedDividend(): float
	{
		return $this->avgExpectedDividend;
	}

	/**
	 * Set the value of avgExpectedDividend
	 *
	 * @param   float  $avgExpectedDividend
	 *
	 * @return  self
	 */
	public function setAvgExpectedDividend(float $avgExpectedDividend): self
	{
		$this->avgExpectedDividend = $avgExpectedDividend;

		return $this;
	}

	/**
	 * Get the value of currentDividend
	 *
	 * @return  float
	 */
	public function getCurrentDividend(): float
	{
		return $this->currentDividend;
	}

	/**
	 * Set the value of currentDividend
	 *
	 * @param   float  $currentDividend
	 *
	 * @return  self
	 */
	public function setCurrentDividend(float $currentDividend): self
	{
		$this->currentDividend = $currentDividend;

		return $this;
	}

	/**
	 * Get the value of currentYearlyYield
	 *
	 * @return  float
	 */
	public function getCurrentYearlyYield(): float
	{
		return $this->currentYearlyYield;
	}

	/**
	 * Set the value of currentYearlyYield
	 *
	 * @param   float  $currentYearlyYield
	 *
	 * @return  self
	 */
	public function setCurrentYearlyYield(float $currentYearlyYield): self
	{
		$this->currentYearlyYield = $currentYearlyYield;

		return $this;
	}

	/**
	 * Get the value of avgYearlyYield
	 *
	 * @return  float
	 */
	public function getAvgYearlyYield(): float
	{
		return $this->avgYearlyYield;
	}

	/**
	 * Set the value of avgYearlyYield
	 *
	 * @param   float  $avgYearlyYield
	 *
	 * @return  self
	 */
	public function setAvgYearlyYield(float $avgYearlyYield): self
	{
		$this->avgYearlyYield = $avgYearlyYield;

		return $this;
	}

	/**
	 * Get the value of dividendPaid
	 *
	 * @return  float
	 */
	public function getDividendPaid(): float
	{
		return $this->dividendPaid;
	}

	/**
	 * Set the value of dividendPaid
	 *
	 * @param   float  $dividendPaid
	 *
	 * @return  self
	 */
	public function setDividendPaid(float $dividendPaid): self
	{
		$this->dividendPaid = $dividendPaid;

		return $this;
	}

	/**
	 * Get the value of taxRate
	 *
	 * @return  float
	 */
	public function getTaxRate(): float
	{
		return $this->taxRate;
	}

	/**
	 * Set the value of taxRate
	 *
	 * @param   float  $taxRate
	 *
	 * @return  self
	 */
	public function setTaxRate(float $taxRate): self
	{
		$this->taxRate = $taxRate;

		return $this;
	}

	/**
	 * Get the value of exchangeRate
	 *
	 * @return  float
	 */
	public function getExchangeRate(): float
	{
		return $this->exchangeRate;
	}

	/**
	 * Set the value of exchangeRate
	 *
	 * @param   float  $exchangeRate
	 *
	 * @return  self
	 */
	public function setExchangeRate(float $exchangeRate): self
	{
		$this->exchangeRate = $exchangeRate;

		return $this;
	}

	/**
	 * Get the value of calendars
	 *
	 * @return  array
	 */
	public function getCalendars(): array
	{
		return $this->calendars;
	}

	/**
	 * Set the value of calendars
	 *
	 * @param   array  $calendars
	 *
	 * @return  self
	 */
	public function setCalendars(array $calendars): self
	{
		$this->calendars = $calendars;

		return $this;
	}

	/**
	 * Get the value of dividend
	 *
	 * @return  array
	 */
	public function getDividend(): array
	{
		return $this->dividend;
	}

	/**
	 * Set the value of dividend
	 *
	 * @param   array  $dividend
	 *
	 * @return  self
	 */
	public function setDividend(array $dividend): self
	{
		$this->dividend = $dividend;

		return $this;
	}

	/**
	 * Get the value of monthlyYield
	 *
	 * @return  float
	 */
	public function getMonthlyYield(): float
	{
		return $this->monthlyYield;
	}

	/**
	 * Set the value of monthlyYield
	 *
	 * @param   float  $monthlyYield
	 *
	 * @return  self
	 */
	public function setMonthlyYield(float $monthlyYield): self
	{
		$this->monthlyYield = $monthlyYield;

		return $this;
	}

	/**
	 * Get the value of price
	 *
	 * @return  float
	 */
	public function getPrice(): float
	{
		$this->price = $this->priceAvgValue / $this->ownedQuantity;
		return $this->price;
	}

	/**
	 * Get the value of avgPrice
	 *
	 * @return  float
	 */
	public function getAvgPrice(): float
	{
		$this->avgPrice = $this->priceAvgInvestedValue / $this->ownedQuantity;
		return $this->avgPrice;
	}
}
