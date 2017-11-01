<?php

namespace Athorrent\Cache\Twig;

use Asm89\Twig\CacheExtension\CacheStrategy\KeyGeneratorInterface;
use Athorrent\Database\Entity\Sharing;
use Athorrent\Database\Entity\User;
use Athorrent\Filesystem\Entry;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\Role;

class KeyGenerator implements KeyGeneratorInterface
{
    private $locale;

    public function __construct($locale)
    {
        $this->locale = $locale;
    }

    public function generateKey($value)
    {
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
        } elseif ($value instanceof Entry) {
            $key = base64_encode($value->getAbsolutePath() . $value->getModificationTime() . ($value->isSharable() ? 0 : 1));
        } elseif ($value instanceof Sharing) {
            $key = $value->getToken();
        } elseif ($value instanceof User) {
            $key = $value->getId() . $value->getConnectionTimestamp();
        } elseif (is_string($value)) {
            $key = $value;
        }

        return $key . $this->locale;
    }
}