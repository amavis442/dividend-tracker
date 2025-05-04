<?php

namespace App\Entity;

use App\Repository\IncomesSharesDataSetRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: IncomesSharesDataSetRepository::class)]
class IncomesSharesDataSet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $totalProfitLoss = null;

    #[ORM\Column]
    private ?float $totalDistribution = null;

    #[ORM\Column]
    private ?float $totalAllocation = null;

    #[ORM\Column]
    private ?float $yield = null;

    #[ORM\Column(type: 'uuid')]
    private ?Uuid $uuid = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotalProfitLoss(): ?float
    {
        return $this->totalProfitLoss;
    }

    public function setTotalProfitLoss(float $totalProfitLoss): static
    {
        $this->totalProfitLoss = $totalProfitLoss;

        return $this;
    }

    public function getTotalDistribution(): ?float
    {
        return $this->totalDistribution;
    }

    public function setTotalDistribution(float $totalDistribution): static
    {
        $this->totalDistribution = $totalDistribution;

        return $this;
    }

    public function getTotalAllocation(): ?float
    {
        return $this->totalAllocation;
    }

    public function setTotalAllocation(float $totalAllocation): static
    {
        $this->totalAllocation = $totalAllocation;

        return $this;
    }

    public function getYield(): ?float
    {
        return $this->yield;
    }

    public function setYield(float $yield): static
    {
        $this->yield = $yield;

        return $this;
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): static
    {
        $this->uuid = $uuid;

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

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
