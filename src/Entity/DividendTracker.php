<?php

namespace App\Entity;

use App\Repository\DividendTrackerRepository;
use Doctrine\Common\Collections\ArrayCollection;
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
    private $sampleDate;

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
    private $dividend = 0.0;

    #[ORM\Column()]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'dividendTrackers')]
    #[ORM\JoinColumn(nullable: true)]
    private $user;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getCreatedAt(): ?\DateTimeInterface
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
