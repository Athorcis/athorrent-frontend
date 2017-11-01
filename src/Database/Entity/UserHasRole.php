<?php

namespace Athorrent\Database\Entity;

/**
 *  @Entity
 */
class UserHasRole
{
    /**
     *  @Id
     *  @ManyToOne(targetEntity="User", inversedBy="hasRoles")
     *  @JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $user;

    /**
     *  @Id
     *  @Column(type="UserRole", nullable=false, options={"collation":"utf8_bin"})
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
