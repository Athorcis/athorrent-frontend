<?php

namespace Athorrent\Security;

use Athorrent\Entity\User;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserProvider implements UserProviderInterface
{
    public function loadUserByUsername($username)
    {
        $user = User::loadByUsername($username);

        if ($user === null) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        if ($user instanceof User) {
            return $this->loadUserByUsername($user->getUsername());
        }

        throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
    }

    public function supportsClass($class)
    {
        return $class === 'Athorrent\Entity\User';
    }
}
