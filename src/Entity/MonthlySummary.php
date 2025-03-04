<?php

namespace App\Entity;

use App\Repository\MonthlySummaryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/*
 * Monthly summary from Trading 212 trhough the mail.
*/
#[ORM\Entity(repositoryClass: MonthlySummaryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class MonthlySummary
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, name: 'ac_date')]
    private ?\DateTimeInterface $acDate = null;

    #[ORM\Column(type: 'float',
        nullable: false,
        options: ["default" => 0],
        name: 'deposit_withdrawal'
    )]
    private float $depositWithdrawal = 0.0;

    #[ORM\Column(type: 'float',
        nullable: false,
        options: ["default" => 0],
        name: 'closed_position_result'
    )]
    private float $closedPositionResult = 0.0;

    #[ORM\Column(type: 'float',
        nullable: false,
        options: ["default" => 0],
        name: 'dividends'
    )]
    private float $dividends = 0.0;

    #[ORM\Column(type: 'float',
        nullable: false,
        options: ["default" => 0],
        name: 'interest_on_uninvested_cash'
    )]
    private float $interestOnUninvestedCash = 0.0;

    #[ORM\Column(type: 'float',
        nullable: false,
        options: ["default" => 0],
        name: 'commissions_and_fees'
    )]
    private float $commissionsAndFees = 0.0;

    #[ORM\Column(type: 'float',
        nullable: false,
        options: ["default" => 0],
        name: 'equity_charges_and_fees'
    )]
    private float $equityChargesAndFees = 0.0;

    #[ORM\Column(type: 'float',
        nullable: false,
        options: ["default" => 0],
        name: 'account_adjustments'
    )]
    private float $accountAdjustments = 0.0;

    #[ORM\Column(type: 'float',
        nullable: false,
        options: ["default" => 0],
        name: 'account_value'
    )]
    private float $accountValue = 0.0;

    #[ORM\Column(type: 'float',
        nullable: false,
        options: ["default" => 0],
        name: 'cash'
    )]
    private float $cash = 0.0;

    #[ORM\Column(type: 'float',
        nullable: false,
        options: ["default" => 0],
        name: 'bonus_non_withdrawable'
    )]
    private float $bonusNonWithdrawable = 0.0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'uuid')]
    private ?Uuid $uuid = null;


    public function __construct()
    {
        $this->setCreatedAt((new \DateTimeImmutable('now')));
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

    public function getAcDate(): ?\DateTimeInterface
    {
        return $this->acDate;
    }

    public function setAcDate(\DateTimeInterface $acDate): static
    {
        $this->acDate = $acDate;

        return $this;
    }

    public function getDepositWithdrawal(): float
    {
        return $this->depositWithdrawal;
    }

    public function setDepositWithdrawal(float $depositWithdrawal): static
    {
        $this->depositWithdrawal = $depositWithdrawal;

        return $this;
    }

    public function getClosedPositionResult(): float
    {
        return $this->closedPositionResult;
    }

    public function setClosedPositionResult(float $closedPositionResult): static
    {
        $this->closedPositionResult = $closedPositionResult;

        return $this;
    }

    public function getDividends(): float
    {
        return $this->dividends;
    }

    public function setDividends(float $dividends): static
    {
        $this->dividends = $dividends;

        return $this;
    }

    public function getInterestOnUninvestedCash(): float
    {
        return $this->interestOnUninvestedCash;
    }

    public function setInterestOnUninvestedCash(float $interestOnUninvestedCash): static
    {
        $this->interestOnUninvestedCash = $interestOnUninvestedCash;

        return $this;
    }

    public function getCommissionsAndFees(): float
    {
        return $this->commissionsAndFees;
    }

    public function setCommissionsAndFees(float $commissionsAndFees): static
    {
        $this->commissionsAndFees = $commissionsAndFees;

        return $this;
    }

    public function getEquityChargesAndFees(): ?float
    {
        return $this->equityChargesAndFees;
    }

    public function setEquityChargesAndFees(float $equityChargesAndFees): static
    {
        $this->equityChargesAndFees = $equityChargesAndFees;

        return $this;
    }

    public function getAccountAdjustments(): float
    {
        return $this->accountAdjustments;
    }

    public function setAccountAdjustments(float $accountAdjustments): static
    {
        $this->accountAdjustments = $accountAdjustments;

        return $this;
    }

    public function getAccountValue(): float
    {
        return $this->accountValue;
    }

    public function setAccountValue(float $accountValue): static
    {
        $this->accountValue = $accountValue;

        return $this;
    }

    public function getCash(): float
    {
        return $this->cash;
    }

    public function setCash(float $cash): static
    {
        $this->cash = $cash;

        return $this;
    }

    public function getBonusNonWithdrawable(): float
    {
        return $this->bonusNonWithdrawable;
    }

    public function setBonusNonWithdrawable(float $bonusNonWithdrawable): static
    {
        $this->bonusNonWithdrawable = $bonusNonWithdrawable;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
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

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }
}
