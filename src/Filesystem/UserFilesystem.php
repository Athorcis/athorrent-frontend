<?php

namespace Athorrent\Filesystem;

use Athorrent\Database\Entity\User;
use Symfony\Component\Filesystem\Path;

class UserFilesystem extends SubFilesystem
{
    protected User $owner;

    public function __construct(User $owner, protected ?User $accessor, string $path = '')
    {
        parent::__construct($this->buildRoot($owner, $path));

        $this->owner = $owner;
    }

    protected function buildRoot(User $owner, string $path): string
    {
        $root = $owner->getFilesPath();

        if ($path !== '') {
            $root = Path::join($root, $path);
        }

        return $root;
    }

    public function getEntry(string $path): UserFilesystemEntry
    {
        return new UserFilesystemEntry($this, $path);
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function isWritable(): bool
    {
        return $this->owner === $this->accessor;
    }
}
