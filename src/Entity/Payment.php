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
     * @ORM\Column(type="datetime")
     */
    private $ex_dividend_date;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $record_date;

    /**
     * @ORM\Column(type="datetime")
     */
    private $pay_date;

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
        return $this->ex_dividend_date;
    }

    public function setExDividendDate(\DateTimeInterface $ex_dividend_date): self
    {
        $this->ex_dividend_date = $ex_dividend_date;

        return $this;
    }

    public function getRecordDate(): ?\DateTimeInterface
    {
        return $this->record_date;
    }

    public function setRecordDate(?\DateTimeInterface $record_date): self
    {
        $this->record_date = $record_date;

        return $this;
    }

    public function getPayDate(): ?\DateTimeInterface
    {
        return $this->pay_date;
    }

    public function setPayDate(\DateTimeInterface $pay_date): self
    {
        $this->pay_date = $pay_date;

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
