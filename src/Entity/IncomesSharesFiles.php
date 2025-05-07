<?php

namespace App\Entity;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

class IncomesSharesFiles
{
    private Collection $files;

    public function __construct()
    {
        $this->files = new ArrayCollection();
    }

     /**
     * @return Collection<int, IncomesSharesFile>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(IncomesSharesFile $file): static
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
        }

        return $this;
    }

    public function removeFile(IncomesSharesFile $file): static
    {
        if ($this->files->removeElement($file)) {
        }

        return $this;
    }
}
