<?php

namespace Athorrent\Security;

use Athorrent\Database\Entity\User;
use Athorrent\Database\Type\UserRole;
use Silex\Application;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserManager implements UserProviderInterface
{
    private $app;

    private $passwordEncoder;

    public function __construct(Application $app, PasswordEncoderInterface $passwordEncoder)
    {
        $this->app = $app;
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

        $entityManager = $this->app['orm.em'];
        $entityManager->persist($user);

        foreach ($user->getHasRoles() as $userRole) {
            $entityManager->persist($userRole);
        }

        $entityManager->flush();
    }

    public function userExists($username)
    {
        return $this->app['orm.repo.user']->findOneBy(['username' => $username]) !== null;
    }

    public function loadUserByUsername($username)
    {
        $user = $this->app['orm.repo.user']->findOneBy(['username' => $username]);

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
            $this->app['orm.repo.user']->delete($id);
        } catch (ORMException $exception) {
            return false;
        }

        return true;
    }

    public function refreshUser(UserInterface $user)
    {
        $class = $this->app['orm.repo.user']->getClassName();

        if ($user instanceof $class) {
            return $this->app['orm.repo.user']->find($user->getId());
        }

        throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
    }

    public function supportsClass($class)
    {
        return $class === $this->app['orm.repo.user']->getClassName();
    }
}
