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
use Symfony\Component\Validator\Exception\ValidationFailedException;
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

        $violations = $this->validator->validate($user);

        if (count($violations) > 0) {
            throw new ValidationFailedException($user, $violations);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        try {
            $this->initUserDirs($user);
            $this->backendManager->addUser($user);
        } catch (\Throwable $e) {
            $this->removeUser($user, true);
            throw $e;
        }
    }

    public function initUserDirs(User $user): void
    {
        $fs = new FileUtils();

        $fs->mkdirAs([
            $user->getFilesPath(),
            $user->getQBittorrentConfigPath(),
        ], 'www-data');
    }

    public function setPlainPassword(User $user, string $password): void
    {
        $user->setPassword($this->hasher->hashPassword($user, $password));
    }

    protected function removeUserDirs(User $user)
    {
        $fs = new FileUtils();
        $fs->remove($user->getPath(''));
    }

    public function removeUser(User $user, $creationCleanup = false): void
    {
        try {
            $this->backendManager->removeUser($user);
        } catch (\Throwable $t) {
            if (!$creationCleanup) {
                throw $t;
            }

            // Backend may never have been registered.
        }

        try {
            $this->removeUserDirs($user);
        } catch (\Throwable $t) {
            if (!$creationCleanup) {
                throw $t;
            }

            // Directory may never have been created.
        }

        // we delete the entity last because we need the entity to contain the id
        try {
            if ($creationCleanup && !$this->entityManager->contains($user)) {
                return;
            }

            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }
        catch (\Throwable $t) {
            if (!$creationCleanup) {
                throw $t;
            }

            // Prefer surfacing the original create failure.
        }
    }
}
