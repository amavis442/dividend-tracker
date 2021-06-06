<?php

namespace App\Entity;

use App\Repository\TaxRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TaxRepository::class)
 * @ORM\HasLifecycleCallbacks
 */
class Tax
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $taxRate;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="date")
     */
    private $validFrom;

    /**
     * @ORM\OneToMany(targetEntity=Ticker::class, mappedBy="tax")
     */
    private $tickers;

    public function __construct()
    {
        $this->tickers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTaxRate(): ?float
    {
        return $this->taxRate / 100;
    }

    public function setTaxRate(int $taxRate): self
    {
        $this->taxRate = $taxRate;

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

    /**
     * Gets triggered only on insert
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->createdAt = new \DateTime("now");
    }

    public function getValidFrom(): ?\DateTimeInterface
    {
        return $this->validFrom;
    }

    public function setValidFrom(\DateTimeInterface $validFrom): self
    {
        $this->validFrom = $validFrom;

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
            $ticker->setTax($this);
        }

        return $this;
    }

    public function removeTicker(Ticker $ticker): self
    {
        if ($this->tickers->removeElement($ticker)) {
            // set the owning side to null (unless already changed)
            if ($ticker->getTax() === $this) {
                $ticker->setTax(null);
            }
        }

        return $this;
    }
}
