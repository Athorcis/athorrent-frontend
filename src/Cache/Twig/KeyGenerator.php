<?php

namespace Athorrent\Cache\Twig;

use Athorrent\Cache\CachableInterface;
use Athorrent\Database\Entity\Sharing;
use Athorrent\Database\Entity\User;
use Psr\Log\InvalidArgumentException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\Role;

class KeyGenerator implements KeyGeneratorInterface
{
    private $locale;

    public function __construct(RequestStack $requestStack)
    {
        $request = $requestStack->getCurrentRequest();

        if ($request) {
            $this->locale = $request->attributes->get('_locale');
        }
    }

    public function generateKey($value)
    {
        if ($value instanceof CachableInterface)
        {
            $key = $value->getCacheKey();
        } elseif ($value === null || $value instanceof TokenInterface) {
            if ($value === null) {
                $key = 'notoken';
            } else {
                $roles = array_map(
                    function (Role $role) {
                        return $role->getRole();
                    }, $value->getRoles()
                );

                if (count($roles) > 0) {
                    $key = implode(',', $roles);
                } else {
                    $key = 'noroles';
                }
            }
        } elseif ($value instanceof Sharing) {
            $key = $value->getToken();
        } elseif ($value instanceof User) {
            $key = $value->getId() . $value->getConnectionTimestamp();
        } elseif (is_array($value)) {
            $key = implode(',', $value);
        } elseif (is_string($value)) {
            $key = $value;
        } else {
            throw new InvalidArgumentException(sprintf('unable to convert object of type %s to cache key', get_class($value)));
        }

        return $key . $this->locale;
    }
}
