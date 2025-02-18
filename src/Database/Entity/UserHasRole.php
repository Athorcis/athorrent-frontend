<?php

namespace Athorrent\Database\Entity;

use Athorrent\Database\Type\UserRole;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class UserHasRole
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'hasRoles')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Id]
    #[ORM\Column(type: 'string', enumType: UserRole::class, nullable: false)]
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
