<?php

declare(strict_types=1);

namespace Athorrent\Filesystem;

use AssertionError;
use Athorrent\Database\Entity\User;
use Athorrent\Database\Repository\SharingRepository;
use Athorrent\SharingNotFoundException;
use Athorrent\Utils\TorrentManagerFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Uid\Uuid;

readonly class FilesystemFactory
{
    public function __construct(private TokenStorageInterface $tokenStorage, private TorrentManagerFactory $torrentManagerFactory, private SharingRepository $sharingRepository)
    {
    }

    protected function getUser(): ?User
    {
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();

        if ($user === null) {
            return null;
        }

        return User::as($user);
    }

    public function createSharedFilesystem(string $id): SharedFilesystem
    {
        $sharing = $this->sharingRepository->find(Uuid::fromString($id));

        if ($sharing === null) {
            throw new SharingNotFoundException();
        }

        $torrentManager = $this->torrentManagerFactory->create($sharing->getUser());

        return new SharedFilesystem($torrentManager, $this->getUser(), $sharing);
    }

    public function createTorrentFilesystem(): TorrentFilesystem
    {
        $user = $this->getUser();

        assert($user instanceof User, new AssertionError('cannot instantiate a torrent manager if no user is logged in'));

        $torrentManager = $this->torrentManagerFactory->create($user);

        return new TorrentFilesystem($torrentManager, $user);
    }
}
