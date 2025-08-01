<?php

namespace App\Entity;

use App\Repository\IncomesSharesDataRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: IncomesSharesDataRepository::class)]
class IncomesSharesData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ticker $ticker = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Position $position = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column]
    private ?float $profitLoss = null;

    #[ORM\Column]
    private ?float $allocation = null;

    #[ORM\Column]
    private ?float $amount = null;

    #[ORM\Column]
    private ?float $distributions = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $dataset = null;

    #[ORM\ManyToOne(inversedBy: 'shares')]
    #[ORM\JoinColumn(nullable: false)]
    private ?IncomesSharesDataSet $incomesSharesDataSet = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPosition(): ?Position
    {
        return $this->position;
    }

    public function setPosition(?Position $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getProfitLoss(): ?float
    {
        return $this->profitLoss;
    }

    public function setProfitLoss(float $profitLoss): static
    {
        $this->profitLoss = $profitLoss;

        return $this;
    }

    public function getAllocation(): ?float
    {
        return $this->allocation;
    }

    public function setAllocation(float $allocation): static
    {
        $this->allocation = $allocation;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDistributions(): ?float
    {
        return $this->distributions;
    }

    public function setDistributions(float $distributions): static
    {
        $this->distributions = $distributions;

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

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDataset(): ?Uuid
    {
        return $this->dataset;
    }

    public function setDataset(?Uuid $dataset): static
    {
        $this->dataset = $dataset;

        return $this;
    }

    public function getIncomesSharesDataSet(): ?IncomesSharesDataSet
    {
        return $this->incomesSharesDataSet;
    }

    public function setIncomesSharesDataSet(?IncomesSharesDataSet $incomesSharesDataSet): static
    {
        $this->incomesSharesDataSet = $incomesSharesDataSet;

        return $this;
    }

}
