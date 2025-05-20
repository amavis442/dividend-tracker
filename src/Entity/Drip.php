<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class Drip
{
	/**
	 * Dividend percentage per year.
	 *
	 * @var float
	 */
	#[Assert\GreaterThan(0)]
	private float $dividendPercentage = 4.0;

	/**
	 * Staring investment
	 *
	 * @var float
	 */
	private ?float $invested = 0.0;

	/**
	 * Investment per month
	 *
	 * @var float
	 */
	private float $investPerMonth = 100.0;

	/**
	 * Inflation
	 *
	 * @var float
	 */
	private ?float $inflation = 0.0;

	/**
	 * How many times does a company pay dividends per year. Default will be 4
	 *
	 * @var int
	 */
	#[Assert\GreaterThan(0)]
	private int $frequency = 12;

	/**
	 *
	 * @var int
	 */
	#[Assert\GreaterThan(0)]
	private int $years = 10;

	/**
	 *
	 * @var float
	 */
	private ?float $taxRate = 0;

	private bool $dividendReinvested = true;

	/**
	 * Get the value of dividendPercentage
	 *
	 * @return  float
	 */
	public function getDividendPercentage(): float
	{
		return $this->dividendPercentage;
	}

	/**
	 * Set the value of dividendPercentage
	 *
	 * @param   float  $dividendPercentage
	 *
	 * @return  self
	 */
	public function setDividendPercentage(?float $dividendPercentage): self
	{
		$this->dividendPercentage = $dividendPercentage ?? 0.0;

		return $this;
	}

	/**
	 * Get the value of invested
	 *
	 * @return  float
	 */
	public function getInvested(): ?float
	{
		return $this->invested;
	}

	/**
	 * Set the value of invested
	 *
	 * @param   float  $invested
	 *
	 * @return  self
	 */
	public function setInvested(?float $invested): self
	{
		$this->invested = $invested;

		return $this;
	}

	/**
	 * Get the value of investPerMonth
	 *
	 * @return  float
	 */
	public function getInvestPerMonth(): float
	{
		return $this->investPerMonth;
	}

	/**
	 * Set the value of investPerMonth
	 *
	 * @param   float  $investPerMonth
	 *
	 * @return  self
	 */
	public function setInvestPerMonth(?float $investPerMonth): self
	{
		$this->investPerMonth = $investPerMonth ?? 0;

		return $this;
	}

	/**
	 * Get the value of frequency
	 *
	 * @return  int
	 */
	public function getFrequency(): int
	{
		return $this->frequency;
	}

	/**
	 * Set the value of frequency
	 *
	 * @param   int  $frequency
	 *
	 * @return  self
	 */
	public function setFrequency(int $frequency): self
	{
		$this->frequency = $frequency;

		return $this;
	}

	/**
	 * Get the value of years
	 *
	 * @return  int
	 */
	public function getYears(): int
	{
		return $this->years;
	}

	/**
	 * Set the value of years
	 *
	 * @param   int  $years
	 *
	 * @return  self
	 */
	public function setYears(int $years): self
	{
		$this->years = $years;

		return $this;
	}

	/**
	 * Get the value of taxRate
	 *
	 * @return  float
	 */
	public function getTaxRate(): ?float
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
	public function setTaxRate(?float $taxRate): self
	{
		$this->taxRate = $taxRate;

		return $this;
	}

	/**
	 * Get the value of inflation
	 *
	 * @return  float
	 */
	public function getInflation(): ?float
	{
		return $this->inflation;
	}

	/**
	 * Set the value of inflation
	 *
	 * @param   float  $inflation
	 *
	 * @return  self
	 */
	public function setInflation(?float $inflation): self
	{
		$this->inflation = $inflation;

		return $this;
	}

	/**
	 * Get the value of dividendReinvested
	 *
	 * @return  bool
	 */
	public function isDividendReinvested(): bool
	{
		return $this->dividendReinvested;
	}

	/**
	 * Set the value of dividendReinvested
	 *
	 * @param   bool  $dividendReinvested
	 *
	 * @return  self
	 */
	public function setDividendReinvested(bool $dividendReinvested): self
	{
		$this->dividendReinvested = $dividendReinvested;

		return $this;
	}
}
