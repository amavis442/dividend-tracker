<?php

namespace App\Entity;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class IncomesSharesFile
{
    /**
     * Holder for raw file data. Not to be persisted.
     *
     * @var UploadedFile
     */
    private UploadedFile $uploadfile;

    public function setFilename(UploadedFile $uploadfile): self
    {
        $this->uploadfile = $uploadfile;
        return $this;
    }

    public function getFilename(): ?UploadedFile
    {
        return $this->uploadfile;
    }
}
