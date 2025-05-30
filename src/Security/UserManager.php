<?php

namespace Athorrent\Security;

use Athorrent\Database\Entity\User;
use Athorrent\Database\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class UserManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private ValidatorInterface $validator,
        private UserPasswordHasherInterface $hasher,
    ) {
    }

    /**
     * @param string|string[] $roles
     */
    public function createUser(string $username, string $password, string|array$roles): void
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $user = new User();

        $user->setUsername($username);
        $this->setPlainPassword($user, $password);
        $user->setRoles($roles);
        $user->setPort($this->userRepository->getNextAvailablePort());

        $this->validator->validate($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $fs = new Filesystem();
        $fs->mkdir($user->getFilesPath());
        $fs->mkdir($user->getNewTorrentsPath());
    }

    public function setPlainPassword(User $user, string $password): void
    {
        $user->setPassword($this->hasher->hashPassword($user, $password));
    }
}
