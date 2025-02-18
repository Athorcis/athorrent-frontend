<?php

namespace Athorrent\Security;

use Athorrent\Database\Entity\User;
use Athorrent\Database\Repository\UserRepository;
use Athorrent\Database\Type\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class UserManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private ValidatorInterface $validator,
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
        $user->setPlainPassword($password);
        $user->setSalt(base64_encode(random_bytes(22)));
        $user->setRoles($roles);
        $user->setPort($this->userRepository->getNextAvailablePort());

        $this->validator->validate($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
