<?php

namespace Athorrent\Security;

use Athorrent\Application\NotifiableException;
use Athorrent\Database\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserManager implements UserProviderInterface
{
    private $entityManager;

    private $userRepository;

    private $passwordEncoder;

    public function __construct(EntityManagerInterface $entityManager, EntityRepository $userRepository, PasswordEncoderInterface $passwordEncoder)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function createUser($username, $password, $roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $rolesDiff = array_diff($roles, UserRole::$values);

        if (count($rolesDiff) > 0) {
            throw new \Exception(sprintf('%s is not a valid role', $rolesDiff[0]));
        }

        $salt = base64_encode(random_bytes(22));
        $encodedPassword = $this->passwordEncoder->encodePassword($password, $salt);

        $user = new User($username, $encodedPassword, $salt, $roles);
        $this->entityManager->persist($user);

        foreach ($user->getHasRoles() as $userRole) {
            $this->entityManager->persist($userRole);
        }

        $this->entityManager->flush();
    }

    public function userExists($username)
    {
        return $this->userRepository->findOneBy(['username' => $username]) !== null;
    }

    public function loadUserByUsername($username)
    {
        $user = $this->userRepository->findOneBy(['username' => $username]);

        if ($user === null) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }

        return $user;
    }

    public function checkUserPassword(User $user, $password)
    {
        return $this->passwordEncoder->encodePassword($password, $user->getSalt()) === $user->getPassword();
    }

    public function setUserPassword(User $user, $password)
    {
        $user->setPassword($this->passwordEncoder->encodePassword($password, $user->getSalt()));
    }

    public function deleteUserById($id)
    {
        try {
            $this->userRepository->delete($id);
        } catch (ORMException $exception) {
            return false;
        }

        return true;
    }

    public function refreshUser(UserInterface $user)
    {
        $class = $this->userRepository->getClassName();

        if ($user instanceof $class) {
            return $this->userRepository->find($user->getId());
        }

        throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
    }

    public function supportsClass($class)
    {
        return $class === $this->userRepository->getClassName();
    }
}
