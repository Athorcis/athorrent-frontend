<?php

namespace Athorrent\Filesystem;

use Athorrent\Cache\KeyGenerator\CacheKeyGetterInterface;
use Athorrent\Database\Entity\Sharing;
use Athorrent\Database\Entity\User;

/** @property UserFilesystem $filesystem */
class UserFilesystemEntry extends SubFilesystemEntry implements CacheKeyGetterInterface
{
    private ?string $sharingToken = null;

    public function __construct(UserFilesystem $filesystem, string $path, FilesystemEntry $internalEntry = null)
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

    public function isCachable(): bool
    {
        return true;
    }

    public function isSharable(): bool
    {
        return $this->filesystem->isWritable();
    }

    public function getSharingToken(): string
    {
        return $this->sharingToken ??= Sharing::generateToken($this->getOwner(), $this->path);
    }

    public function isShared(): bool
    {
        return isset($this->getOwner()->getSharings()[$this->getSharingToken()]);
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
