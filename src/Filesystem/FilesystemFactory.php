<?php

namespace Athorrent\Filesystem;

use AssertionError;
use Athorrent\Database\Entity\User;
use Athorrent\Database\Repository\SharingRepository;
use Athorrent\Utils\TorrentManagerFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class FilesystemFactory
{
    public function __construct(private TokenStorageInterface $tokenStorage, private TorrentManagerFactory $torrentManagerFactory, private SharingRepository $sharingRepository)
    {
    }

    protected function getUser(): ?User
    {
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();

        if ($user instanceof UserInterface) {
            assert($user instanceof User, "invalid type of user");
        }

        return $user;
    }

    public function createSharedFilesystem(string $token): SharedFilesystem
    {
        $sharing = $this->sharingRepository->findOneBy(['token' => $token]);

        if ($sharing === null) {
            throw new NotFoundHttpException('error.sharingNotFound');
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
