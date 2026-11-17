<?php

namespace App\Entity;

class FileUpload
{
    private ?string $uploadfile = null;

    public function getUploadFile(): ?string
    {
        return $this->uploadfile;
    }

    public function setUploadFile(string $uploadfile): self
    {
        $this->uploadfile = $uploadfile;

        return $this;
    }
}
