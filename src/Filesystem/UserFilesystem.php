<?php

namespace Athorrent\Filesystem;

use Athorrent\Database\Entity\User;
use Symfony\Component\Filesystem\Path;

class UserFilesystem extends SubFilesystem
{
    /** @var User */
    protected $owner;

    /** @var User */
    protected $accessor;

    public function __construct(User $owner, ?User $accessor, string $path = '')
    {
        parent::__construct($this->buildRoot($owner, $path));

        $this->owner = $owner;
        $this->accessor = $accessor;
    }

    /**
     * @param User $owner
     * @param string $path
     * @return string
     */
    protected function buildRoot(User $owner, string $path): string
    {
        $root = Path::join(FILES_DIR, $owner->getId());

        if ($path !== '') {
            $root = Path::join($root, $path);
        }

        return $root;
    }

    /**
     * @param string $path
     * @return UserFilesystemEntry
     */
    public function getEntry(string $path): FilesystemEntryInterface
    {
        return new UserFilesystemEntry($this, $path);
    }

    /**
     * @return User
     */
    public function getOwner(): User
    {
        return $this->owner;
    }

    /**
     * @return bool
     */
    public function isWritable(): bool
    {
        return $this->owner === $this->accessor;
    }
}
