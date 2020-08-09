<?php

namespace Athorrent\Security;

use Athorrent\Database\Entity\User;
use Athorrent\Database\Repository\UserRepository;
use Athorrent\Database\Type\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserManager
{
    private $entityManager;

    private $userRepository;

    private $passwordEncoder;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function createUser($username, $password, $roles): void
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $rolesDiff = array_diff($roles, UserRole::$values);

        if (count($rolesDiff) > 0) {
            throw new \InvalidArgumentException(sprintf('%s is not a valid role', $rolesDiff[0]));
        }

        $salt = base64_encode(random_bytes(22));
        $user = new User($username, $password, $salt, $roles);

        $this->entityManager->persist($user);

        $this->entityManager->flush();
    }

    public function userExists($username): bool
    {
        return $this->userRepository->findOneBy(['username' => $username]) !== null;
    }

    public function checkUserPassword(User $user, $password): bool
    {
        return $this->passwordEncoder->encodePassword($user, $password) === $user->getPassword();
    }

    public function setUserPassword(User $user, $password): void
    {
        $user->setPassword($this->passwordEncoder->encodePassword($password, $user->getSalt()));
    }

    public function deleteUserById($id): bool
    {
        try {
            $this->userRepository->delete($id);
        } catch (ORMException $exception) {
            return false;
        }

        return true;
    }
}
