<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\CalendarRepository')]
#[ORM\HasLifecycleCallbacks]
class Calendar
{
    public const SOURCE_SCRIPT = 'script';
    public const SOURCE_MANUEL = 'manual';
    public const REGULAR = 'Regular';
    public const SUPPLEMENT = 'Supplement';
    public const SPECIAL = 'Special';


    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Ticker', inversedBy: 'calendars')]
    #[ORM\JoinColumn(nullable: false)]
    private $ticker;

    #[ORM\Column(type: 'date', name: 'ex_dividend_date')]
    private $exDividendDate;

    #[ORM\Column(type: 'date', name: 'record_date')]
    private $recordDate;

    #[ORM\Column(type: 'date', name: 'payment_date')]
    private $paymentDate;

    #[ORM\Column(
        type: 'float',
        name: 'cash_amount',
        nullable: false,
        options: ["default" => 0]
    )]
    private $cashAmount = 0.0;

    #[ORM\OneToMany(targetEntity: 'App\Entity\Payment', mappedBy: 'calendar')]
    #[ORM\JoinColumn(nullable: true)]
    #[ORM\OrderBy(['payDate' => 'DESC'])]
    private $payments;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Currency')]
    private $currency;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $dividendType;

    /**
     * Was this added manual or script?
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $source;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $description;

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $updatedAt;

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

    public function getCashAmount(): ?float
    {
        return $this->cashAmount;
    }

    public function getNetCashAmount(): ?float
    {
        return ($this->cashAmount * (1 - (Constants::TAX / 100)) / Constants::EXCHANGE);
    }

    public function setCashAmount(float $cashAmount): self
    {
        $this->cashAmount = number_format($cashAmount, 3);

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

    public function getDividendType(): ?string
    {
        return $this->dividendType ?? self::REGULAR;
    }

    public function setDividendType(?string $dividendType): self
    {
        $this->dividendType = $dividendType;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Gets triggered only on insert
     */
    #[ORM\PrePersist]
    public function onPrePersist()
    {
        $this->createdAt = new \DateTime("now");
    }

    /**
     * Gets triggered every time on update
     */
    #[ORM\PreUpdate]
    public function onPreUpdate()
    {
        $this->updatedAt = new \DateTime("now");
    }
}
