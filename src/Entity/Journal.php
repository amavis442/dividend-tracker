<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\JournalRepository;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['journal:read', 'journal:read:item']],
    denormalizationContext: ['groups' => ['journal:write']],
    security: 'is_granted("ROLE_USER")',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Patch(),
        new Delete()
    ]
)]
#[ORM\Entity(repositoryClass: JournalRepository::class)]
class Journal
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Groups('journal:read', 'journal:write')]
    #[ORM\Column(type: 'text')]
    private $content;

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $updatedAt;

    #[Groups('journal:read', 'journal:write')]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $title;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\User', inversedBy: 'journals')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;

    #[Groups('journal:read', 'journal:write')]
    #[ORM\JoinTable(name: 'journal_taxonomy')]
    #[ORM\JoinColumn(name: 'journal_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'taxonomy_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: Taxonomy::class, inversedBy: 'journals')]
    #[ORM\OrderBy(['title' => 'DESC'])]
    private $taxonomy;

    public function __construct()
    {
        $this->taxonomy = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Taxonomy>|null
     */
    public function getTaxonomy(): ?Collection
    {
        return $this->taxonomy;
    }

    public function addTaxonomy(Taxonomy $taxonomy): self
    {
        if (!$this->taxonomy->contains($taxonomy)) {
            $this->taxonomy[] = $taxonomy;
        }

        return $this;
    }

    public function removeTaxonomy(Taxonomy $taxonomy): self
    {
        $this->taxonomy->removeElement($taxonomy);

        return $this;
    }
}
