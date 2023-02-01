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

    /**
     * @return User
     */
    public function getOwner(): User
    {
        return $this->filesystem->getOwner();
    }

    /**
     * @return bool
     */
    public function isWritable(): bool
    {
        return $this->filesystem->isWritable();
    }

    /**
     * @return bool
     */
    public function isCachable(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isSharable(): bool
    {
        return $this->filesystem->isWritable();
    }

    /**
     * @return string
     */
    public function getSharingToken(): string
    {
        return $this->sharingToken ??= Sharing::generateToken($this->getOwner(), $this->path);
    }

    /**
     * @return bool
     */
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
