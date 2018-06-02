<?php

namespace Athorrent\Database\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="Athorrent\Database\Repository\UserRepository")
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="username", columns={"username"})})
 */
class User implements UserInterface
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false, options={"unsigned": true})
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=32, nullable=false, options={"collation": "utf8_bin"})
     */
    private $username;

    /**
     * @var string
     */
    private $plainPassword;

    /**
     * @var string
     * @ORM\Column(type="string", length=88, nullable=false, options={"collation": "utf8_bin", "fixed": true})
     */
    private $password;

    /**
     * @var string
     * @ORM\Column(type="string", length=32, nullable=false, options={"collation": "utf8_bin", "fixed": true})
     */
    private $salt;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $creationDateTime;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $connectionDateTime;

    /**
     * @var UserHasRole[]
     * @ORM\OneToMany(targetEntity="UserHasRole", mappedBy="user", cascade={"persist"}, fetch="EAGER")
     */
    private $hasRoles;

    /**
     * @var Sharing[]
     * @ORM\OneToMany(targetEntity="Sharing", mappedBy="user", indexBy="token")
     */
    private $sharings;

    public function __construct($username, $plainPassword, $salt, array $roles)
    {
        $this->username = $username;
        $this->plainPassword = $plainPassword;
        $this->salt = $salt;
        $this->creationDateTime = new \DateTime();

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

    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;
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

    public function setConnectionDateTime(\DateTime $dateTime)
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
        $this->plainPassword = null;
    }
}
