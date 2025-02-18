<?php

namespace Athorrent\Database\Type;

enum UserRole: string {
    case User = 'ROLE_USER';
    case Admin = 'ROLE_ADMIN';
}
