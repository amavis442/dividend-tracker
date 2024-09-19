<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['branch:read']],
    denormalizationContext: ['groups' => ['branch:write']],
    security: 'is_granted("ROLE_USER")',
    operations: [
        new Get(),
        new GetCollection()
    ]
)]
#[ORM\Entity(repositoryClass: 'App\Repository\BranchRepository')]
class Branch
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    private ?int $id = null;

    #[Groups(['branch:read', 'branch:write', 'ticker:read:item'])]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Branch', inversedBy: 'branches')]
    private $parent;

    #[Groups(['branch:read', 'branch:write', 'ticker:read:item'])]
    #[ORM\OneToMany(targetEntity: 'App\Entity\Branch', mappedBy: 'parent')]
    private Collection $branches;

    #[Groups(['branch:read', 'branch:write', 'ticker:read:item'])]
    #[ORM\Column(type: 'string', length: 255)]
    private string $label;

    #[ORM\OneToMany(targetEntity: 'App\Entity\Ticker', mappedBy: 'branch')]
    private Collection $tickers;

    #[Groups(['branch:read', 'branch:write', 'ticker:read:item'])]
    #[ORM\Column(type: 'integer', nullable: true, name: 'asset_allocation')]
    private ?int $assetAllocation = 0;

    #[Groups(['branch:read', 'branch:write', 'ticker:read:item'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function __construct()
    {
        $this->branches = new ArrayCollection();
        $this->tickers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getBranches(): Collection
    {
        return $this->branches;
    }

    public function addBranch(self $branch): self
    {
        if (!$this->branches->contains($branch)) {
            $this->branches[] = $branch;
            $branch->setParent($this);
        }

        return $this;
    }

    public function removeBranch(self $branch): self
    {
        if ($this->branches->contains($branch)) {
            $this->branches->removeElement($branch);
            // set the owning side to null (unless already changed)
            if ($branch->getParent() === $this) {
                $branch->setParent(null);
            }
        }

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return Collection|Ticker[]
     */
    public function getTickers(): Collection
    {
        return $this->tickers;
    }

    public function addTicker(Ticker $ticker): self
    {
        if (!$this->tickers->contains($ticker)) {
            $this->tickers[] = $ticker;
            $ticker->setBranch($this);
        }

        return $this;
    }

    public function removeTicker(Ticker $ticker): self
    {
        if ($this->tickers->contains($ticker)) {
            $this->tickers->removeElement($ticker);
            // set the owning side to null (unless already changed)
            if ($ticker->getBranch() === $this) {
                $ticker->setBranch(null);
            }
        }

        return $this;
    }

    public function getAssetAllocation(): ?float
    {
        return $this->assetAllocation / 100;
    }

    public function setAssetAllocation(?float $assetAllocation): self
    {
        $this->assetAllocation = $assetAllocation * 100;

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
}
