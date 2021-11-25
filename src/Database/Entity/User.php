<?php

namespace Athorrent\Database\Entity;

use Athorrent\Cache\KeyGenerator\CacheKeyGetterInterface;
use Athorrent\Process\Entity\TrackedProcess;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="Athorrent\Database\Repository\UserRepository")
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="username", columns={"username"})})
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface, CacheKeyGetterInterface
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
     * @ORM\Column(type="string", length=32, nullable=false, options={"collation": "utf8mb4_bin"})
     */
    private $username;

    /**
     * @var string
     */
    private $plainPassword;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=false, options={"collation": "utf8mb4_bin"})
     */
    private $password;

    /**
     * @var string
     * @ORM\Column(type="string", length=32, nullable=false, options={"collation": "utf8mb4_bin", "fixed": true})
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

    /**
     * @var TrackedProcess
     * @ORM\OneToOne(targetEntity="Athorrent\Process\Entity\TrackedProcess", fetch="LAZY")
     */
    private $athorrentProcess;

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

    public function getId(): int
    {
        return (int)$this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername($username): void
    {
        $this->username = $username;
    }

    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword($password): void
    {
        $this->password = $password;
    }

    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function getCreationTimestamp(): int
    {
        return $this->creationDateTime->getTimestamp();
    }

    public function getConnectionTimestamp(): int
    {
        if ($this->connectionDateTime === null) {
            return 0;
        }

        return $this->connectionDateTime->getTimestamp();
    }

    public function setConnectionDateTime(\DateTime $dateTime): void
    {
        $this->connectionDateTime = $dateTime;
    }

    public function getHasRoles()
    {
        return $this->hasRoles;
    }

    public function getRoles(): array
    {
        $roles = [];

        foreach ($this->hasRoles as $hasRole) {
            $roles[] = $hasRole->getRole();
        }

        return $roles;
    }

    /**
     * @return Sharing[]|ArrayCollection
     */
    public function getSharings()
    {
        return $this->sharings;
    }

    /**
     * @return TrackedProcess
     */
    public function getAthorrentProcess(): ?TrackedProcess
    {
        return $this->athorrentProcess;
    }

    /**
     * @param TrackedProcess|null $process
     */
    public function setAthorrentProcess(?TrackedProcess $process): void
    {
        $this->athorrentProcess = $process;
    }

    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    public function getCacheKey(): string
    {
        return (string)$this->id;
    }
}
