<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

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

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Payment", mappedBy="calendar")
     * @ORM\JoinColumn(nullable=true)
     * @ORM\OrderBy({"payDate" = "DESC"})
     */
    private $payments;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency")
     */
    private $currency;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
    }

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
        $this->recordDate = $exDividendDate;
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
        if ($this->paymentDate instanceof DateTime) {
            if ($current->format('Ymd') > $this->paymentDate->format('Ymd')) {
                return null;
            }
            if ($current->format('Ymd') === $this->paymentDate->format('Ymd')) {
                return 0;
            }
            return (int) $current->diff($this->paymentDate)->format('%a') + 1;
        }
        return null;
    }

    /**
     * @return Collenction|Payment[]
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): self
    {
        if (!$this->payments->contains($payment)) {
            $this->payments[] = $payment;
            $payment->setCalendar($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        if ($this->payments->contains($payment)) {
            $this->payments->removeElement($payment);
            // set the owning side to null (unless already changed)
            if ($payment->getCalendar() === $this) {
                $payment->setCalendar(null);
            }
        }

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
}
