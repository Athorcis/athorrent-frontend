<?php

namespace Athorrent\Database\Type;

enum UserRole: string {
    case User = 'ROLE_USER';
    case Admin = 'ROLE_ADMIN';

    public static function fromOrSelf(string|UserRole $value): UserRole
    {
        if ($value instanceof self) {
            return $value;
        }

        return self::from($value);
    }
}
