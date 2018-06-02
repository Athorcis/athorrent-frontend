<?php

namespace Athorrent\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class UserHasRole
{
    /**
     * @var User
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="User", inversedBy="hasRoles")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $user;

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="UserRole", nullable=false, options={"collation": "utf8_bin"})
     */
    private $role;

    public function __construct(User $user, $role)
    {
        $this->user = $user;
        $this->role = $role;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getRole()
    {
        return $this->role;
    }
}
