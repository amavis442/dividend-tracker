<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    private string $fileName = '';
    private int $fileSize = 0;
    private string $originalName = '';

    public function __construct(
        #[Autowire('%documents_directory%')] private string $targetDirectory,
        private SluggerInterface $slugger
    ) {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
    }

    public function upload(UploadedFile $file): string
    {
        $originalFilename = pathinfo(
            $file->getClientOriginalName(),
            PATHINFO_FILENAME
        );
        $this->originalName = $originalFilename;
        $safeFilename = transliterator_transliterate(
            'Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()',
            $originalFilename
        );
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName =
            $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();


        $this->fileSize = $file->getSize();
        try {
            $file->move($this->getTargetDirectory(), $fileName);
        } catch (FileException $e) {
            throw $e;
        }

        $this->fileName = $fileName;
        return $fileName;
    }

    public function getSize(): int
    {
        return $this->fileSize;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getName(): string
    {
        return $this->fileName;
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }
}
