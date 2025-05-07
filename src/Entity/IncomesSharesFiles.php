<?php

namespace App\Entity;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class IncomesSharesFiles
{
    private Collection $files;

    public function __construct()
    {
        $this->files = new ArrayCollection();
    }

     /**
     * @return Collection<int, File>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(UploadedFile $file): static
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
        }

        return $this;
    }

    public function removeFile(UploadedFile $file): static
    {
        if ($this->files->removeElement($file)) {
        }

        return $this;
    }
}
