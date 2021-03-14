<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BranchRepository")
 */
class Branch
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Branch", inversedBy="branches")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Branch", mappedBy="parent")
     */
    private $branches;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $label;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Ticker", mappedBy="branch")
     */
    private $tickers;

    /**
     * @ORM\Column(type="integer", nullable=true, name="asset_allocation")
     */
    private $assetAllocation;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

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
