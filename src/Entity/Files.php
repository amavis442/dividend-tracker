<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity(repositoryClass: 'App\Repository\FilesRepository')]
class Files
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private $filename;

    /**
     * Holder for raw file data. Not to be persisted.
     *
     * @var File
     */
    private $file;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function setFile(File $file): self
    {
        $this->file = $file;
        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }
}
