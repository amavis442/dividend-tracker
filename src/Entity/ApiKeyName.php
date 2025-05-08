<?php

namespace App\Entity;

use App\Repository\ApiKeyNameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ApiKeyNameRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ApiKeyName
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column(length: 255)]
	private ?string $keyName = null;


	#[ORM\Column(name: 'created_at')]
	private ?\DateTimeImmutable $createdAt = null;

	#[ORM\Column(name: 'updated_at', nullable: true)]
	private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, ApiKey>
     */
    #[ORM\OneToMany(targetEntity: ApiKey::class, mappedBy: 'apiKeyName', orphanRemoval: true)]
    private Collection $apiKeys;

    public function __construct()
    {
        $this->apiKeys = new ArrayCollection();
    }

	#[ORM\PrePersist]
	public function setCreatedAtValue(): void
	{
		$this->createdAt = new \DateTimeImmutable();
		$this->setUpdatedAtValue();
	}

	#[ORM\PreUpdate]
	public function setUpdatedAtValue(): void
	{
		$this->updatedAt = new \DateTimeImmutable();
	}

	#[Assert\Callback]
	public function validate(ExecutionContextInterface $context, $payload)
	{
		if (empty($this->getKeyName())) {
			$context
				->buildViolation('Api key name should be filled!')
				->atPath('keyName')
				->addViolation();
		}
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getKeyName(): ?string
	{
		return $this->keyName;
	}

	public function setKeyName(string $keyName): static
	{
		$this->keyName = $keyName;

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

	public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
	{
		$this->updatedAt = $updatedAt;

		return $this;
	}

    /**
     * @return Collection<int, ApiKey>
     */
    public function getApiKeys(): Collection
    {
        return $this->apiKeys;
    }

    public function addApiKey(ApiKey $apiKey): static
    {
        if (!$this->apiKeys->contains($apiKey)) {
            $this->apiKeys->add($apiKey);
            $apiKey->setApiKeyName($this);
        }

        return $this;
    }

    public function removeApiKey(ApiKey $apiKey): static
    {
        if ($this->apiKeys->removeElement($apiKey)) {
            // set the owning side to null (unless already changed)
            if ($apiKey->getApiKeyName() === $this) {
                $apiKey->setApiKeyName(null);
            }
        }

        return $this;
    }
}
