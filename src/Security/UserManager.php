<?php

declare(strict_types=1);

namespace Athorrent\Security;

use Athorrent\Backend\BackendManagerProxy;
use Athorrent\Database\Entity\User;
use Athorrent\Database\Repository\UserRepository;
use Athorrent\Database\Type\UserRole;
use Athorrent\Filesystem\FileUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class UserManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private ValidatorInterface $validator,
        private UserPasswordHasherInterface $hasher,
        private BackendManagerProxy $backendManager,
    ) {
    }

    /**
     * @param string|UserRole|UserRole[]|string[] $roles
     */
    public function createUser(string $username, string $password, mixed $roles, ?string $clientIp = null): void
    {
        if (!is_array($roles)) {
            $roles = [$roles];
        }

        $user = new User();

        $user->setUsername($username);
        $this->setPlainPassword($user, $password);
        $user->setRoles($roles);
        $user->setPort($this->userRepository->getNextAvailablePort());
        $user->setClientType(User::CLIENT_TYPE_QBITTORRENT);

        if ($clientIp) {
            $user->setClientIp($clientIp);
        }

        $this->validator->validate($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->initUserDirs($user);

        $this->backendManager->addUser($user);
    }

    public function initUserDirs(User $user): void
    {
        $fs = new FileUtils();

        $fs->mkdirAs([
            $user->getFilesPath(),
            $user->getNewTorrentsPath(),
            $user->getQBittorrentConfigPath(),
        ], 'www-data');
    }

    public function setPlainPassword(User $user, string $password): void
    {
        $user->setPassword($this->hasher->hashPassword($user, $password));
    }

    public function removeUser(User $user): void
    {
        $this->backendManager->removeUser($user);

        $fs = new FileUtils();
        $fs->remove($user->getPath(''));

        // we delete the entity last because we need the entity to contain the id
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
}
