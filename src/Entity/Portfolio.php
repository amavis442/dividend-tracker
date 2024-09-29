<?php

namespace App\Entity;

use App\Repository\PortfolioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\Uid\Uuid;

#[HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: PortfolioRepository::class)]
class Portfolio
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'portfolio', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?float $goal = 0.0;

    #[ORM\Column]
    private ?float $invested = 0.0;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\Column(type: 'uuid')]
    private ?Uuid $uuid = null;

    #[ORM\Column]
    private ?int $numActivePosition = 0;

    #[ORM\Column]
    private ?float $profit = 0.0;

    #[ORM\Column]
    private ?float $totalDividend = 0.0;

    #[ORM\Column]
    private ?float $allocated = 0.0;

    #[ORM\Column]
    private ?float $goalpercentage = 0.0;

    /**
     * @var Collection<int, Position>
     */
    #[ORM\OneToMany(targetEntity: Position::class, mappedBy: 'portfolio')]
    private Collection $positions;

    public function __construct()
    {
        $this->positions = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->created_at = new \DateTimeImmutable();
        $this->setUpdatedAtValue();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updated_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getGoal(): ?float
    {
        return $this->goal;
    }

    public function setGoal(float $goal): static
    {
        $this->goal = $goal;

        return $this;
    }

    public function getInvested(): ?float
    {
        return $this->invested;
    }

    public function setInvested(float $invested): static
    {
        $this->invested = $invested;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
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

    public function getNumActivePosition(): ?int
    {
        return $this->numActivePosition;
    }

    public function setNumActivePosition(int $numActivePosition): static
    {
        $this->numActivePosition = $numActivePosition;

        return $this;
    }

    public function getProfit(): ?float
    {
        return $this->profit;
    }

    public function setProfit(float $profit): static
    {
        $this->profit = $profit;

        return $this;
    }

    public function getTotalDividend(): ?float
    {
        return $this->totalDividend;
    }

    public function setTotalDividend(float $totalDividend): static
    {
        $this->totalDividend = $totalDividend;

        return $this;
    }

    public function getAllocated(): ?float
    {
        return $this->allocated;
    }

    public function setAllocated(float $allocated): static
    {
        $this->allocated = $allocated;

        return $this;
    }

    public function getGoalpercentage(): ?float
    {
        return $this->goalpercentage;
    }

    public function setGoalpercentage(float $goalpercentage): static
    {
        $this->goalpercentage = $goalpercentage;

        return $this;
    }

    /**
     * @return Collection<int, Position>
     */
    public function getPositions(): Collection
    {
        return $this->positions;
    }

    public function addPosition(Position $position): static
    {
        if (!$this->positions->contains($position)) {
            $this->positions->add($position);
            $position->setPortfolio($this);
        }

        return $this;
    }

    public function removePosition(Position $position): static
    {
        if ($this->positions->removeElement($position)) {
            // set the owning side to null (unless already changed)
            if ($position->getPortfolio() === $this) {
                $position->setPortfolio(null);
            }
        }

        return $this;
    }
}
