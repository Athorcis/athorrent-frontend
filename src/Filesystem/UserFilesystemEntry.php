<?php

namespace Athorrent\Filesystem;

use Athorrent\Cache\CachableInterface;
use Athorrent\Database\Entity\Sharing;
use Athorrent\Database\Entity\User;

/** @property UserFilesystem $filesystem */
class UserFilesystemEntry extends SubFilesystemEntry implements CachableInterface
{
    /** @var string */
    private $sharingToken;

    public function __construct(UserFilesystem $filesystem, string $path, FilesystemEntry $internalEnty = null)
    {
        parent::__construct($filesystem, $path, $internalEnty);
    }

    public function getCacheKey(): string
    {
        return base64_encode($this->internalEntry->path . $this->getModificationTimestamp() . ($this->isSharable() ? 0 : 1));
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
    public function getSharingToken()
    {
        if ($this->sharingToken === null) {
            $path = $this->path;

            if (!$this->isFile()) {
                $path .= '/';
            }

            $this->sharingToken = Sharing::generateToken($this->getOwner(), $path);
        }

        return $this->sharingToken;
    }

    /**
     * @return bool
     */
    public function isShared()
    {
        return isset($this->getOwner()->getSharings()[$this->getSharingToken()]);
    }
}
