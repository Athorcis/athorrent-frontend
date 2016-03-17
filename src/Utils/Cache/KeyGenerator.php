<?php

namespace Athorrent\Utils\Cache;

use Asm89\Twig\CacheExtension\CacheStrategy\KeyGeneratorInterface;
use Athorrent\Entity\Sharing;
use Athorrent\Entity\User;
use Athorrent\Utils\File;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\Role;

class KeyGenerator implements KeyGeneratorInterface
{
    public function generateKey($value)
    {
        global $app;

        if ($value === null || $value instanceof TokenInterface) {
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
        } elseif ($value instanceof File) {
            $key = $value->getAbsolutePath() . $value->getModificationTime() . ($value->isSharable() ? 0 : 1);
        } elseif ($value instanceof Sharing) {
            $key = $value->getToken();
        } elseif ($value instanceof User) {
            $key = $value->getUserId() . $value->getConnectionTimestamp();
        } elseif (is_string($value)) {
            $key = $value;
        }

        return $key . $app['locale'];
    }
}
