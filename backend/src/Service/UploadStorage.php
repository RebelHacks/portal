<?php

namespace App\Service;

use App\Entity\File;
use App\Entity\User;
use App\Enum\FileKind;
use Aws\S3\S3Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;
use Symfony\Component\HttpFoundation\File\UploadedFile;

// Saves uploads under a random name: in a local folder, or in the private S3 bucket when
// UPLOAD_STORAGE is uploads.s3. The name the browser sent is only kept in the database.
class UploadStorage
{
    // What save() names files. Anything else in storage isn't ours, so the cleanup leaves it alone.
    private const STORED_NAME = '/^[0-9a-f]{32}\.[a-z0-9]+$/';

    public function __construct(
        #[Autowire('%env(UPLOAD_STORAGE)%')]
        private string $storage,
        #[Autowire('%app.upload_dir%')]
        private string $folder,
        #[Autowire('%env(S3_BUCKET)%')]
        private string $bucket,
        // a closure, so the S3 client is only built on servers that use it
        #[AutowireServiceClosure(S3Client::class)]
        private \Closure $s3Client,
    ) {
    }

    // Stores the upload and returns its row, not yet persisted.
    /** @throws UploadFailed */
    public function save(UploadedFile $upload, User $owner, FileKind $kind): File
    {
        $file = (new File())
            ->setOwner($owner)
            ->setKind($kind)
            ->setStoredName(bin2hex(random_bytes(16)) . '.' . ($upload->guessExtension() ?? 'bin'))
            ->setOriginalName(mb_substr($upload->getClientOriginalName(), 0, 255))
            ->setMimeType((string) $upload->getMimeType())
            ->setSize((int) $upload->getSize());

        try {
            if ($this->inBucket()) {
                // No ACL is sent. The bucket blocks public access, so only the portal can read the object.
                $this->s3()->putObject([
                    'Bucket' => $this->bucket,
                    'Key' => $file->getStoredName(),
                    'SourceFile' => $upload->getPathname(),
                    'ContentType' => $file->getMimeType(),
                ]);
            } else {
                $this->copyToFolder($upload->getPathname(), $file->getStoredName());
            }
        } catch (\Throwable $reason) {
            throw new UploadFailed('The upload could not be stored.', previous: $reason);
        }

        return $file;
    }

    // For when saving the row fails after the file was stored.
    public function delete(File $file): void
    {
        $this->remove($file->getStoredName());
    }

    // Deletes stored files older than $cutoff whose names aren't in $keep, and returns their names.
    // Nothing chooses a name to delete: it only ever removes files the portal itself named.
    /** @param array<string, true> $keep @return list<string> */
    public function sweep(array $keep, \DateTimeImmutable $cutoff, bool $dryRun): array
    {
        $deleted = [];

        foreach ($this->stored() as $name => $changedAt) {
            if (!preg_match(self::STORED_NAME, $name) || isset($keep[$name]) || $changedAt > $cutoff->getTimestamp()) {
                continue;
            }

            if (!$dryRun) {
                $this->remove($name);
            }
            $deleted[] = $name;
        }

        return $deleted;
    }

    private function inBucket(): bool
    {
        return match ($this->storage) {
            'uploads.s3' => true,
            'uploads.local' => false,
            // a typo must not quietly save resumes somewhere unexpected
            default => throw new \LogicException('UPLOAD_STORAGE must be uploads.local or uploads.s3.'),
        };
    }

    private function s3(): S3Client
    {
        return ($this->s3Client)();
    }

    private function copyToFolder(string $from, string $name): void
    {
        // 0700 and 0600: only the account the portal runs as can open them
        if (!is_dir($this->folder) && !mkdir($this->folder, 0700, true) && !is_dir($this->folder)) {
            throw new \RuntimeException('Could not create the upload folder.');
        }

        $path = $this->folder . '/' . $name;

        if (!copy($from, $path) || !chmod($path, 0600)) {
            throw new \RuntimeException('Could not write the upload.');
        }
    }

    private function remove(string $name): void
    {
        if ($this->inBucket()) {
            $this->s3()->deleteObject(['Bucket' => $this->bucket, 'Key' => $name]);

            return;
        }

        $path = $this->folder . '/' . $name;

        if (is_file($path) && !unlink($path)) {
            throw new \RuntimeException('Could not delete a stored upload.');
        }
    }

    // Every stored file's name and when it last changed.
    /** @return iterable<string, int> */
    private function stored(): iterable
    {
        if ($this->inBucket()) {
            // the delimiter keeps the listing to the bucket's top level, where save() puts everything
            $pages = $this->s3()->getPaginator('ListObjectsV2', ['Bucket' => $this->bucket, 'Delimiter' => '/']);

            foreach ($pages as $page) {
                foreach ($page['Contents'] ?? [] as $object) {
                    yield $object['Key'] => $object['LastModified']->getTimestamp();
                }
            }

            return;
        }

        if (!is_dir($this->folder)) {
            return;
        }

        foreach (new \FilesystemIterator($this->folder) as $entry) {
            if ($entry->isFile()) {
                yield $entry->getFilename() => $entry->getMTime();
            }
        }
    }
}
