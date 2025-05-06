<?php

namespace App\Entity;

use App\Repository\IncomesSharesDataSetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    private ?float $totalProfitLoss = 0.0;

    #[ORM\Column]
    private ?float $totalDistribution = 0.0;

    #[ORM\Column]
    private ?float $totalAllocation = 0.0;

    #[ORM\Column]
    private ?float $yield = 0.0;

    #[ORM\Column(type: 'uuid')]
    private ?Uuid $uuid = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, IncomesSharesData>
     */
    #[ORM\OneToMany(targetEntity: IncomesSharesData::class, mappedBy: 'incomesSharesDataSet', orphanRemoval: true)]
    private Collection $shares;

    public function __construct()
    {
        $this->shares = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, IncomesSharesData>
     */
    public function getShares(): Collection
    {
        return $this->shares;
    }

    public function addShare(IncomesSharesData $share): static
    {
        if (!$this->shares->contains($share)) {
            $this->shares->add($share);
            $share->setIncomesSharesDataSet($this);
        }

        return $this;
    }

    public function removeShare(IncomesSharesData $share): static
    {
        if ($this->shares->removeElement($share)) {
            // set the owning side to null (unless already changed)
            if ($share->getIncomesSharesDataSet() === $this) {
                $share->setIncomesSharesDataSet(null);
            }
        }

        return $this;
    }
}
