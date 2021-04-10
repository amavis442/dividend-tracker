<?php

namespace App\Entity;

use App\Repository\DividendTrackerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DividendTrackerRepository::class)
 */
class DividendTracker
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="date")
     */
    private $sampleDate;

    /**
     * @ORM\Column(type="integer")
     */
    private $principle;

    /**
     * @ORM\Column(type="integer")
     */
    private $dividend;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="dividendTrackers")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    public function __construct()
    {
        $this->user = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSampleDate(): ?\DateTimeInterface
    {
        return $this->sampleDate;
    }

    public function setSampleDate(\DateTimeInterface $sampleDate): self
    {
        $this->sampleDate = $sampleDate;

        return $this;
    }

    public function getPrinciple(): ?float
    {
        return $this->principle / Constants::VALUTA_PRECISION;
    }

    public function setPrinciple(float $principle): self
    {
        $this->principle = $principle * Constants::VALUTA_PRECISION;

        return $this;
    }

    public function getDividend(): ?float
    {
        return $this->dividend / Constants::VALUTA_PRECISION;
    }

    public function setDividend(float $dividend): self
    {
        $this->dividend = $dividend * Constants::VALUTA_PRECISION;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
