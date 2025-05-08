<?php

namespace App\Entity;

use App\Repository\ApiKeyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ApiKeyRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ApiKey
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\ManyToOne(inversedBy: 'apikeys')]
	private ?User $user = null;

	#[ORM\Column(length: 255)]
	private ?string $apiKey = null;

	#[ORM\Column]
	private ?\DateTimeImmutable $createdAt = null;

	#[ORM\Column(nullable: true)]
	private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'apiKeys')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ApiKeyName $apiKeyName = null;

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
		if (empty($this->getApiKey())) {
			$context
				->buildViolation('Api key should be filled!')
				->atPath('apiKey')
				->addViolation();
		}
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getUser(): ?User
	{
		return $this->user;
	}

	public function setUser(?User $user): static
	{
		$this->user = $user;

		return $this;
	}

	public function getApiKey(): ?string
	{
		return $this->apiKey;
	}

	public function setApiKey(string $apiKey): static
	{
		$this->apiKey = $apiKey;

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

	public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
	{
		$this->updatedAt = $updatedAt;

		return $this;
	}

    public function getApiKeyName(): ?ApiKeyName
    {
        return $this->apiKeyName;
    }

    public function setApiKeyName(?ApiKeyName $apiKeyName): static
    {
        $this->apiKeyName = $apiKeyName;

        return $this;
    }
}
