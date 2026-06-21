<?php

declare(strict_types=1);

namespace Athorrent\Filesystem;

use Athorrent\Cache\KeyGenerator\CacheKeyGetterInterface;
use Athorrent\Database\Entity\Sharing;
use Athorrent\Database\Entity\User;

/** @property UserFilesystem $filesystem */
class UserFilesystemEntry extends SubFilesystemEntry implements CacheKeyGetterInterface
{
    private ?Sharing $sharing = null;

    public function __construct(UserFilesystem $filesystem, string $path, FilesystemEntry|null $internalEntry = null)
    {
        parent::__construct($filesystem, $path, $internalEntry);
    }

    public function getOwner(): User
    {
        return $this->filesystem->getOwner();
    }

    public function isWritable(): bool
    {
        return $this->filesystem->isWritable();
    }

    public function isDeletable(): bool
    {
        return $this->filesystem->isWritable();
    }

    public function isCachable(): bool
    {
        return true;
    }

    public function isSharable(): bool
    {
        return $this->filesystem->isWritable();
    }

    private function findSharing(): ?Sharing
    {
        if ($this->sharing !== null) {
            return $this->sharing;
        }

        return $this->sharing = $this->getOwner()->getSharings()[$this->path] ?? null;
    }

    public function getSharingId(): ?string
    {
        $sharing = $this->findSharing();

        return $sharing?->getId()->toRfc4122();
    }

    public function isShared(): bool
    {
        return $this->findSharing() !== null;
    }

    public function getCacheKey(): string
    {
        $sharable = $this->isSharable();
        $rawKey = $this->internalEntry->path . $this->getModificationTimestamp() . (int)$sharable;

        if ($sharable) {
            $rawKey .= (int)$this->isShared();
        }

        return base64_encode($rawKey);
    }
}
