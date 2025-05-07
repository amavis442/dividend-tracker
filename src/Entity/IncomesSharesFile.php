<?php

namespace App\Entity;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class IncomesSharesFile
{
    /**
     * Holder for raw file data. Not to be persisted.
     *
     * @var string
     */
    private string $uploadfile;

    public function setFilename(string $uploadfile): self
    {
        $this->uploadfile = $uploadfile;
        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->uploadfile;
    }
}
