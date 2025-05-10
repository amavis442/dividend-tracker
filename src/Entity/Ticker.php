<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[
    ApiResource(
        normalizationContext: ['groups' => ['ticker:read', 'ticker:read:item']],
        denormalizationContext: ['groups' => ['ticker:write']],
        security: 'is_granted("ROLE_USER")',
        operations: [new Get(), new GetCollection()]
    )
]
#[ORM\Entity(repositoryClass: 'App\Repository\TickerRepository')]
#[UniqueEntity('isin')]
#[HasLifecycleCallbacks]
class Ticker
{
    //#[ApiProperty(identifier: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Groups(['ticker:read', 'ticker:write'])]
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $symbol;

    #[Groups(['ticker:read', 'ticker:write'])]
    #[ORM\Column(type: 'string', length: 255)]
    private string $fullname;

    #[Groups(['ticker:read', 'ticker:write'])]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Branch', inversedBy: 'tickers')]
    #[ORM\JoinColumn(nullable: false)]
    private $branch;

    //#[Groups(['ticker:read', 'ticker:write'])]
    #[ORM\OneToMany(targetEntity: 'App\Entity\Calendar', mappedBy: 'ticker')]
    #[ORM\OrderBy(['paymentDate' => 'DESC'])]
    private $calendars;

    //#[Groups(['ticker:read', 'ticker:write'])]
    #[ORM\OneToMany(targetEntity: 'App\Entity\Research', mappedBy: 'ticker')]
    #[ORM\OrderBy(['createdAt'=>'DESC'])]
    private $researches;

    //#[Groups(['ticker:read', 'ticker:write'])]
    #[ORM\OneToMany(targetEntity: 'App\Entity\Payment', mappedBy: 'ticker')]
    #[ORM\OrderBy(['payDate' => 'DESC'])]
    private $payments;

    #[Groups(['ticker:read', 'ticker:write'])]
    #[ORM\OneToMany(targetEntity: 'App\Entity\Position', mappedBy: 'ticker')]
    #[ORM\OrderBy(['id' => 'DESC'])]
    private $positions;

    //#[Groups(['ticker:read', 'ticker:write'])]
    #[
        ORM\ManyToMany(
            targetEntity: 'App\Entity\DividendMonth',
            inversedBy: 'tickers',
            indexBy: 'dividendMonth'
        )
    ]
    private $dividendMonths;

    //#[ApiProperty(identifier: true)]
    #[Groups(['ticker:read', 'ticker:write'])]
    #[Assert\Isin]
    #[ORM\Column(type: 'string', length: 255, nullable: false, unique: true)]
    private string $isin;

    #[ORM\ManyToOne(targetEntity: Tax::class, inversedBy: 'tickers')]
    private $tax;

    #[Groups(['ticker:read', 'ticker:write'])]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Currency')]
    private $currency;

    #[Groups(['ticker:read', 'ticker:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[Groups(['ticker:read', 'ticker:write'])]
    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $uuid = null;

    /**
     * @var Collection<int, TickerAlternativeSymbol>
     */
    #[ORM\OneToMany(targetEntity: TickerAlternativeSymbol::class, mappedBy: 'ticker', orphanRemoval: true)]
    private Collection $tickerAlternativeSymbols;

    /**
     * @var Collection<int, Trading212PieInstrument>
     */
    #[ORM\OneToMany(targetEntity: Trading212PieInstrument::class, mappedBy: 'ticker')]
    private Collection $trading212PieInstruments;

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

    public function __construct()
    {
        $this->calendars = new ArrayCollection();
        $this->researches = new ArrayCollection();
        $this->dividendMonths = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->positions = new ArrayCollection();
        $this->tickerAlternativeSymbols = new ArrayCollection();
        $this->trading212PieInstruments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = strtoupper($symbol);

        return $this;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setFullname(string $fullname): self
    {
        $this->fullname = $fullname;

        return $this;
    }

    public function getBranch(): ?Branch
    {
        return $this->branch;
    }

    public function setBranch(?Branch $branch): self
    {
        $this->branch = $branch;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getCalendars(): Collection
    {
        return $this->calendars;
    }

    public function addCalendar(Calendar $calendar): self
    {
        if (!$this->calendars->contains($calendar)) {
            $this->calendars[] = $calendar;
            $calendar->setTicker($this);
        }

        return $this;
    }

    public function removeCalendar(Calendar $calendar): self
    {
        if ($this->calendars->contains($calendar)) {
            $this->calendars->removeElement($calendar);
            // set the owning side to null (unless already changed)
            if ($calendar->getTicker() === $this) {
                $calendar->setTicker(null);
            }
        }

        return $this;
    }

    public function hasCalendar(): bool
    {
        if (count($this->calendars) > 0) {
            return true;
        }

        return false;
    }

    public function getRecentDividendDate(): ?Calendar
    {
        $isRegularDividend = false;
        if ($this->calendars->count() < 1) {
            return null;
        }
        $index = 0;
        if ($this->calendars[0]->getDividendType() === Calendar::REGULAR) {
            $index = 0;
            $isRegularDividend = true;
        }

        if (
            !$isRegularDividend &&
            $this->calendars[1]->getPaymentDate()->format('Ymd') ===
                $this->calendars[0]->getPaymentDate()->format('Ymd') &&
            $this->calendars[1]->getDividendType() === Calendar::REGULAR
        ) {
            $index = 1;
            $isRegularDividend = true;
        }
        if (
            !$isRegularDividend &&
            $this->calendars[2]->getPaymentDate()->format('Ymd') ===
                $this->calendars[0]->getPaymentDate()->format('Ymd') &&
            $this->calendars[2]->getDividendType() === Calendar::REGULAR
        ) {
            $index = 2;
            $isRegularDividend = true;
        }

        return $this->calendars[$index];
    }

    public function isDividendPayMonth(int $currentMonth): bool
    {
        if (
            $this->getDividendMonths() instanceof Collection &&
            !$this->getDividendMonths()->isEmpty()
        ) {
            return $this->getDividendMonths()->exists(function (
                $key,
                \App\Entity\DividendMonth $dividendMonth
            ) use ($currentMonth): bool {
                return (int) $dividendMonth->getDividendMonth() ===
                    $currentMonth;
            });
        }
        return false;
    }

    public function getPayoutFrequency(): int
    {
        return $this->getDividendMonths()->count() ?: 0;
    }

    /**
     * @return Collection|Research[]
     */
    public function getResearches(): Collection
    {
        return $this->researches;
    }

    public function addResearch(Research $research): self
    {
        if (!$this->researches->contains($research)) {
            $this->researches[] = $research;
            $research->setTicker($this);
        }

        return $this;
    }

    public function removeResearch(Research $research): self
    {
        if ($this->researches->contains($research)) {
            $this->researches->removeElement($research);
            // set the owning side to null (unless already changed)
            if ($research->getTicker() === $this) {
                $research->setTicker(null);
            }
        }

        return $this;
    }

    public function hasResearch(): bool
    {
        return $this->researches->count() > 0;
    }

    /**
     * @return Collection|null
     */
    public function getDividendMonths(): ?Collection
    {
        return $this->dividendMonths;
    }

    public function addDividendMonth(DividendMonth $dividendMonth): self
    {
        if (!$this->dividendMonths->contains($dividendMonth)) {
            $this->dividendMonths[] = $dividendMonth;
        }

        return $this;
    }

    public function removeDividendMonth(DividendMonth $dividendMonth): self
    {
        if ($this->dividendMonths->contains($dividendMonth)) {
            $this->dividendMonths->removeElement($dividendMonth);
        }

        return $this;
    }

    public function getDividendFrequency(): int
    {
        if ($this->dividendMonths) {
            return count($this->dividendMonths);
        }
        return 0;
    }

    public function getIsin(): ?string
    {
        return $this->isin;
    }

    public function setIsin(?string $isin): self
    {
        $this->isin = $isin;

        return $this;
    }

    /**
     * @return Collection|Payment[]
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    /**
     * @return Collection|Position[]
     */
    public function getPositions(): Collection
    {
        return $this->positions;
    }

    public function getTax(): ?Tax
    {
        return $this->tax;
    }

    public function setTax(?Tax $tax): self
    {
        $this->tax = $tax;

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function setUuid(?Uuid $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return Collection<int, TickerAlternativeSymbol>
     */
    public function getTickerAlternativeSymbols(): Collection
    {
        return $this->tickerAlternativeSymbols;
    }

    public function addTickerAlternativeSymbol(TickerAlternativeSymbol $tickerAlternativeSymbol): static
    {
        if (!$this->tickerAlternativeSymbols->contains($tickerAlternativeSymbol)) {
            $this->tickerAlternativeSymbols->add($tickerAlternativeSymbol);
            $tickerAlternativeSymbol->setTicker($this);
        }

        return $this;
    }

    public function removeTickerAlternativeSymbol(TickerAlternativeSymbol $tickerAlternativeSymbol): static
    {
        if ($this->tickerAlternativeSymbols->removeElement($tickerAlternativeSymbol)) {
            // set the owning side to null (unless already changed)
            if ($tickerAlternativeSymbol->getTicker() === $this) {
                $tickerAlternativeSymbol->setTicker(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Trading212PieInstrument>
     */
    public function getTrading212PieInstruments(): Collection
    {
        return $this->trading212PieInstruments;
    }

    public function addTrading212PieInstrument(Trading212PieInstrument $trading212PieInstrument): static
    {
        if (!$this->trading212PieInstruments->contains($trading212PieInstrument)) {
            $this->trading212PieInstruments->add($trading212PieInstrument);
            $trading212PieInstrument->setTicker($this);
        }

        return $this;
    }

    public function removeTrading212PieInstrument(Trading212PieInstrument $trading212PieInstrument): static
    {
        if ($this->trading212PieInstruments->removeElement($trading212PieInstrument)) {
            // set the owning side to null (unless already changed)
            if ($trading212PieInstrument->getTicker() === $this) {
                $trading212PieInstrument->setTicker(null);
            }
        }

        return $this;
    }

}
