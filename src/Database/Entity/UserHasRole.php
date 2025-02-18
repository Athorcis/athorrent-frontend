<?php

namespace Athorrent\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class UserHasRole
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'hasRoles')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Id]
    #[ORM\Column(type: 'UserRole', nullable: false)]
    private string $role;

    public function __construct(User $user, string $role)
    {
        $this->user = $user;
        $this->role = $role;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRole(): string
    {
        return $this->role;
    }
}
