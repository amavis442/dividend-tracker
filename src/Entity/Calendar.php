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
     * @ORM\Column(type="date", name="ex_dividend_date")
     */
    private $exDividendDate;

    /**
     * @ORM\Column(type="date", name="record_date")
     */
    private $recordDate;

    /**
     * @ORM\Column(type="date", name="payment_date")
     */
    private $paymentDate;

    /**
     * @ORM\Column(type="integer", name="cash_amount")
     */
    private $cashAmount;

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

    public function setRecordDate(\DateTimeInterface $recordDate): self
    {
        $this->recordDate = $recordDate;

        return $this;
    }

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(\DateTimeInterface $paymentDate): self
    {
        $this->paymentDate = $paymentDate;

        return $this;
    }

    public function getCashAmount(): ?int
    {
        return $this->cashAmount;
    }

    public function setCashAmount(int $cashAmount): self
    {
        $this->cashAmount = $cashAmount;

        return $this;
    }

    public function getDaysLeft(): ?int
    {
        $current = new DateTime();
        if ($this->exDividendDate instanceof DateTime && $this->exDividendDate >= $current) {
            return (int) (new DateTime())->diff($this->exDividendDate)->format('%d') + 1;
        }
        return null;
    }
}
