<?php

namespace Athorrent\Database\Type;

class UserRole extends Enum
{
    public static $values = ['ROLE_USER', 'ROLE_ADMIN'];

    public function getValues()
    {
        return self::$values;
    }
}
