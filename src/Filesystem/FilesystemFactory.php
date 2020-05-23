<?php

namespace Athorrent\Filesystem;

use Athorrent\Database\Entity\Sharing;
use Athorrent\Database\Repository\SharingRepository;
use Athorrent\Utils\TorrentManagerFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FilesystemFactory
{
    private $tokenStorage;

    private $torrentManagerFactory;

    private $sharingRepository;

    public function __construct(TokenStorageInterface $tokenStorage, TorrentManagerFactory $torrentManagerFactory, SharingRepository $sharingRepository)
    {
        $this->tokenStorage = $tokenStorage;
        $this->torrentManagerFactory = $torrentManagerFactory;
        $this->sharingRepository = $sharingRepository;
    }

    public function createSharedFilesystem(string $token): SharedFilesystem
    {
        /** @var Sharing $sharing */
        $sharing = $this->sharingRepository->findOneBy(['token' => $token]);

        if ($sharing === null) {
            throw new NotFoundHttpException('error.sharingNotFound');
        }

        $user = $this->tokenStorage->getToken()->getUser();
        $torrentManager = $this->torrentManagerFactory->create($sharing->getUser());

        return new SharedFilesystem($torrentManager, $user, $sharing);
    }

    public function createTorrentFilesystem(): TorrentFilesystem
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $torrentManager = $this->torrentManagerFactory->create($user);

        return new TorrentFilesystem($torrentManager, $user);
    }
}
