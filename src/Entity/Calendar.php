<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CalendarRepository")
 */
class Calendar
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Ticker", inversedBy="calendars")
     * @ORM\JoinColumn(nullable=false)
     */
    private $ticker;

    /**
     * @ORM\Column(type="date")
     */
    private $ex_dividend_date;

    /**
     * @ORM\Column(type="date")
     */
    private $record_date;

    /**
     * @ORM\Column(type="date")
     */
    private $payment_date;

    /**
     * @ORM\Column(type="integer")
     */
    private $cash_amount;

    public function getId(): ?int
    {
        return $this->id;
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

    public function setRecordDate(\DateTimeInterface $record_date): self
    {
        $this->record_date = $record_date;

        return $this;
    }

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->payment_date;
    }

    public function setPaymentDate(\DateTimeInterface $payment_date): self
    {
        $this->payment_date = $payment_date;

        return $this;
    }

    public function getCashAmount(): ?int
    {
        return $this->cash_amount;
    }

    public function setCashAmount(int $cash_amount): self
    {
        $this->cash_amount = $cash_amount;

        return $this;
    }

    public function getDaysLeft(): ?int
    {
        if ($this->ex_dividend_date instanceof DateTime){
            return (int)(new DateTime())->diff($this->ex_dividend_date)->format('%d');
        }
        return null;
    }
}
