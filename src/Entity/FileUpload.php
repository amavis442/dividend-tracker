<?php

namespace App\Entity;

class FileUpload
{
    private $uploadfile;

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
