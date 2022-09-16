<?php

namespace Athorrent\Security;

use Athorrent\Database\Entity\User;
use Athorrent\Database\Repository\UserRepository;
use Athorrent\Database\Type\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserManager
{
    public function __construct(private EntityManagerInterface $entityManager, private UserRepository $userRepository, private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function createUser(string $username, string $password, $roles): void
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $rolesDiff = array_diff($roles, UserRole::$values);

        if (count($rolesDiff) > 0) {
            throw new InvalidArgumentException(sprintf('%s is not a valid role', $rolesDiff[0]));
        }

        $salt = base64_encode(random_bytes(22));
        $user = new User($username, $password, $salt, $roles);

        $this->entityManager->persist($user);

        $this->entityManager->flush();
    }

    public function userExists(string $username): bool
    {
        return $this->userRepository->findOneBy(['username' => $username]) !== null;
    }

    public function checkUserPassword(User $user, string $password): bool
    {
        return $this->passwordHasher->hashPassword($user, $password) === $user->getPassword();
    }

    public function setUserPassword(User $user, string $password): void
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
    }
}
