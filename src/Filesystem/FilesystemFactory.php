<?php

namespace Athorrent\Filesystem;

use Athorrent\Database\Entity\User;
use Athorrent\Database\Repository\SharingRepository;
use Athorrent\Utils\TorrentManagerFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FilesystemFactory
{
    public function __construct(private TokenStorageInterface $tokenStorage, private TorrentManagerFactory $torrentManagerFactory, private SharingRepository $sharingRepository)
    {
    }

    protected function getUser(): ?User
    {
        $token = $this->tokenStorage->getToken();
        return $token?->getUser();
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
        $torrentManager = $this->torrentManagerFactory->create($user);

        return new TorrentFilesystem($torrentManager, $user);
    }
}
