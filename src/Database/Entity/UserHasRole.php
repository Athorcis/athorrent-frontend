<?php

namespace Athorrent\Database\Entity;

use Athorrent\Database\Type\UserRole;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Cache;

#[ORM\Entity]
#[Cache(usage: 'READ_ONLY')]
class UserHasRole
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'hasRoles')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Cache(usage: 'NONSTRICT_READ_WRITE')]
    private User $user;

    #[ORM\Id]
    #[ORM\Column(nullable: false, enumType: UserRole::class)]
    private UserRole $role;

    public function __construct(User $user, UserRole $role)
    {
        $this->user = $user;
        $this->role = $role;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }
}
