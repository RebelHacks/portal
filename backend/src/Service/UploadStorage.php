<?php

namespace App\Service;

use App\Entity\File;
use App\Entity\User;
use App\Enum\FileKind;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

// Saves uploads outside public/ under a random name. The name the browser sent is only kept in the database.
class UploadStorage
{
    public function __construct(
        #[Autowire('%app.upload_dir%')]
        private string $uploadDir,
    ) {
    }

    // Moves the upload into place and returns its row, not yet persisted.
    public function save(UploadedFile $upload, User $owner, FileKind $kind): File
    {
        if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0700, true) && !is_dir($this->uploadDir)) {
            throw new \RuntimeException('Could not create the upload folder.');
        }

        // read these before moving, while the temporary file still exists
        $file = (new File())
            ->setOwner($owner)
            ->setKind($kind)
            ->setStoredName(bin2hex(random_bytes(16)) . '.' . ($upload->guessExtension() ?? 'bin'))
            ->setOriginalName(mb_substr($upload->getClientOriginalName(), 0, 255))
            ->setMimeType((string) $upload->getMimeType())
            ->setSize((int) $upload->getSize());

        $upload->move($this->uploadDir, $file->getStoredName());

        return $file;
    }

    // For when saving the row fails after the file was moved.
    public function delete(File $file): void
    {
        $path = $this->uploadDir . '/' . $file->getStoredName();

        if (is_file($path)) {
            unlink($path);
        }
    }
}
