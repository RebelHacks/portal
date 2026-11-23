<?php

namespace App\Service;

use App\Entity\File;
use App\Entity\User;
use App\Enum\FileKind;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use Symfony\Component\HttpFoundation\File\UploadedFile;

// Saves uploads under a random name, in a local folder or the private S3 bucket (flysystem.yaml
// says which). The name the browser sent is only kept in the database.
class UploadStorage
{
    // What save() names files. Anything else in storage isn't ours, so the cleanup leaves it alone.
    private const STORED_NAME = '/^[0-9a-f]{32}\.[a-z0-9]+$/';

    public function __construct(
        // the "uploads.storage" entry in flysystem.yaml
        private FilesystemOperator $uploadsStorage,
    ) {
    }

    // Stores the upload and returns its row, not yet persisted.
    public function save(UploadedFile $upload, User $owner, FileKind $kind): File
    {
        $file = (new File())
            ->setOwner($owner)
            ->setKind($kind)
            ->setStoredName(bin2hex(random_bytes(16)) . '.' . ($upload->guessExtension() ?? 'bin'))
            ->setOriginalName(mb_substr($upload->getClientOriginalName(), 0, 255))
            ->setMimeType((string) $upload->getMimeType())
            ->setSize((int) $upload->getSize());

        $stream = fopen($upload->getPathname(), 'r');

        try {
            // private: readable only by the portal, on disk and in the bucket
            $this->uploadsStorage->writeStream($file->getStoredName(), $stream, [
                'visibility' => 'private',
                'directory_visibility' => 'private',
            ]);
        } finally {
            if (\is_resource($stream)) {
                fclose($stream);
            }
        }

        return $file;
    }

    // For when saving the row fails after the file was stored.
    public function delete(File $file): void
    {
        $this->uploadsStorage->delete($file->getStoredName());
    }

    // Deletes stored files older than $cutoff whose names aren't in $keep, and returns their names.
    // Nothing chooses a name to delete: it only ever removes files the portal itself named.
    /** @param array<string, true> $keep @return list<string> */
    public function sweep(array $keep, \DateTimeImmutable $cutoff, bool $dryRun): array
    {
        $deleted = [];

        /** @var StorageAttributes $entry */
        foreach ($this->uploadsStorage->listContents('') as $entry) {
            $name = $entry->path();

            if (!$entry->isFile() || !preg_match(self::STORED_NAME, $name) || isset($keep[$name]) || $entry->lastModified() > $cutoff->getTimestamp()) {
                continue;
            }

            if (!$dryRun) {
                $this->uploadsStorage->delete($name);
            }
            $deleted[] = $name;
        }

        return $deleted;
    }
}
