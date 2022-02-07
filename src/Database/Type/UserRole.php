<?php

namespace Athorrent\Database\Type;

class UserRole extends Enum
{
    /** @var string[] */
    public static array $values = ['ROLE_USER', 'ROLE_ADMIN'];

    public function getValues(): array
    {
        return self::$values;
    }
}
