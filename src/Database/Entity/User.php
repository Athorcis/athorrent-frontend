<?php

namespace Athorrent\Database\Entity;

use DateTime;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 *  @Entity(repositoryClass="Athorrent\Database\Repository\UserRepository")
 *  @Table(uniqueConstraints={@UniqueConstraint(name="username", columns={"username"})})
 */
class User implements UserInterface
{
    /**
     *  @Id
     *  @Column(type="integer", nullable=false, options={"unsigned": true})
     *  @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     *  @Column(type="string", length=32, nullable=false, options={"collation": "utf8_bin"})
     */
    private $username;

    /**
     *  @Column(type="string", length=88, nullable=false, options={"collation": "utf8_bin", "fixed": true})
     */
    private $password;

    /**
     *  @Column(type="string", length=32, nullable=false, options={"collation": "utf8_bin", "fixed": true})
     */
    private $salt;

    /**
     *  @Column(type="datetime", nullable=false)
     */
    private $creationDateTime;

    /**
     *  @Column(type="datetime", nullable=true)
     */
    private $connectionDateTime;

    /**
     *  @OneToMany(targetEntity="UserHasRole", mappedBy="user")
     */
    private $hasRoles;

    /**
     *  @OneToMany(targetEntity="Sharing", mappedBy="user", indexBy="token")
     */
    private $sharings;

    public function __construct($username, $password, $salt, array $roles)
    {
        $this->username = $username;
        $this->password = $password;
        $this->salt = $salt;
        $this->creationDateTime = new DateTime();

        $this->hasRoles = array_map(function ($role) {
            return new UserHasRole($this, $role);
        }, $roles);
    }

    public function getId()
    {
        return intval($this->id);
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }
    
    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function getCreationTimestamp()
    {
        return $this->creationDateTime->getTimestamp();
    }

    public function getConnectionTimestamp()
    {
        if ($this->connectionDateTime === null) {
            return 0;
        }

        return $this->connectionDateTime->getTimestamp();
    }

    public function setConnectionDateTime(DateTime $dateTime)
    {
        $this->connectionDateTime = $dateTime;
    }

    public function getHasRoles()
    {
        return $this->hasRoles;
    }

    public function getRoles()
    {
        $roles = [];

        foreach ($this->hasRoles as $hasRole) {
            $roles[] = $hasRole->getRole();
        }

        return $roles;
    }

    public function getSharings()
    {
        return $this->sharings;
    }

    public function eraseCredentials()
    {
    }
}
