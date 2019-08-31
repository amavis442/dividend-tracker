<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PaymentRepository")
 */
class Payment
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", name = "ex_dividend_date" )
     */
    private $exDividendDate;

    /**
     * @ORM\Column(type="datetime", name = "record_date", nullable=true)
     */
    private $recordDate;

    /**
     * @ORM\Column(type="datetime", name = "pay_date")
     */
    private $payDate;

    /**
     * @ORM\Column(type="integer")
     */
    private $dividend;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Position", inversedBy="payments")
     */
    private $position;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Ticker", inversedBy="payments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $ticker;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExDividendDate(): ?\DateTimeInterface
    {
        return $this->exDividendDate;
    }

    public function setExDividendDate(\DateTimeInterface $exDividendDate): self
    {
        $this->exDividendDate = $exDividendDate;

        return $this;
    }

    public function getRecordDate(): ?\DateTimeInterface
    {
        return $this->recordDate;
    }

    public function setRecordDate(?\DateTimeInterface $recordDate): self
    {
        $this->recordDate = $recordDate;

        return $this;
    }

    public function getPayDate(): ?\DateTimeInterface
    {
        return $this->payDate;
    }

    public function setPayDate(\DateTimeInterface $payDate): self
    {
        $this->payDate = $payDate;

        return $this;
    }

    public function getDividend(): ?int
    {
        return $this->dividend;
    }

    public function setDividend(int $dividend): self
    {
        $this->dividend = $dividend;

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
}
