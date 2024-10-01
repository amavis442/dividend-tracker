<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Position;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['pie:read']],
    denormalizationContext: ['groups' => ['pie:write']],
    security: 'is_granted("ROLE_USER")',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Delete(),
        new Put(),
        new Patch()
    ]
)]
#[ORM\Table(name: 'pie')]
#[ORM\Entity(repositoryClass: 'App\Repository\PieRepository')]
class Pie
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Groups(['pie:read', 'pie:write', 'position:read:item', 'transaction:read:item'])]
    #[ORM\Column(type: 'string', length: 255)]
    private $label;

    #[ORM\ManyToMany(targetEntity: 'App\Entity\Position', mappedBy: 'pies')]
    private $positions;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'pies')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\OneToMany(targetEntity: 'App\Entity\Transaction', mappedBy: 'pie')]
    private $transactions;

    #[ORM\Column(options: ["default" => 0.0])]
    private float $goal = 0.0;


    public function __construct()
    {
        $this->positions = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
     * @return Collection|Position[]
     */
    public function getPositions(): Collection
    {
        return $this->positions;
    }

    public function addPosition(Position $position): self
    {
        if (!$this->positions->contains($position)) {
            $this->positions[] = $position;
        }

        return $this;
    }

    public function removePosition(Position $position): self
    {
        $this->positions->removeElement($position);

        return $this;
    }

    /**
     * @return Collection|Transaction[]
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
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

    public function getGoal(): float
    {
        return $this->goal;
    }

    public function setGoal(float $goal): static
    {
        $this->goal = $goal;

        return $this;
    }
}
