<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['attachment:read']],
    denormalizationContext: ['groups' => ['attachment:write']],
    security: 'is_granted("ROLE_USER")',
    operations: [
        new Get(),
        new GetCollection()
    ]
)]
#[HasLifecycleCallbacks]
#[ORM\Entity]
class Attachment
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    private ?int $id = null;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     */
    private ?UploadedFile $attachmentFile = null;

    #[Groups('attachment:read', 'attachment:write', 'research:read:item')]
    #[ORM\Column(type: 'string')]
    private string $attachmentName;

    #[Groups('attachment:read', 'attachment:write', 'research:read:item')]
    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $attachmentSize = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Research', inversedBy: 'attachments')]
    private $research;

    #[Groups('attachment:read', 'attachment:write', 'research:read:item')]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $label = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
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

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile|null $attachmentFile
     */
    public function setAttachmentFile(?UploadedFile $attachmentFile = null): void
    {
        $this->attachmentFile = $attachmentFile;

        if (null !== $attachmentFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getAttachmentFile(): ?UploadedFile
    {
        return $this->attachmentFile;
    }

    public function setAttachmentName(string $attachmentName): self
    {
        $this->attachmentName = $attachmentName;

        return $this;
    }

    public function getAttachmentName(): ?string
    {
        return $this->attachmentName;
    }

    public function setAttachmentSize(int $attachmentSize): self
    {
        $this->attachmentSize = $attachmentSize;

        return $this;
    }

    public function getAttachmentSize(): ?int
    {
        return $this->attachmentSize > 0 ? $this->attachmentSize : 0;
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

    public function getResearch(): ?Research
    {
        return $this->research;
    }

    public function setResearch(?Research $research): self
    {
        $this->research = $research;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }
}
