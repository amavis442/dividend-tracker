<?php

namespace App\Entity;

use App\Repository\DividendTrackerRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[ORM\Entity(repositoryClass: DividendTrackerRepository::class)]
#[HasLifecycleCallbacks]
class DividendTracker
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    private ?int $id = null;

    #[ORM\Column(type: 'date')]
    private \DateTime $sampleDate;

    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private $principle = 0.0;


    #[ORM\Column(
        type: 'float',
        nullable: false,
        options: ["default" => 0]
    )]
    private float $dividend = 0.0;

    #[ORM\Column()]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'dividendTrackers')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSampleDate(): ?\DateTime
    {
        return $this->sampleDate;
    }

    public function setSampleDate(\DateTime $sampleDate): self
    {
        $this->sampleDate = $sampleDate;

        return $this;
    }

    public function getPrinciple(): float
    {
        return $this->principle;
    }

    public function setPrinciple(float $principle): self
    {
        $this->principle = $principle;

        return $this;
    }

    public function getDividend(): float
    {
        return $this->dividend;
    }

    public function setDividend(float $dividend): self
    {
        $this->dividend = $dividend;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
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
