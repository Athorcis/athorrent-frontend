<?php

namespace Athorrent\Database\Entity;

use Athorrent\Cache\KeyGenerator\CacheKeyGetterInterface;
use Athorrent\Database\Repository\UserRepository;
use Athorrent\Process\Entity\TrackedProcess;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Table]
#[ORM\UniqueConstraint(name: 'username', columns: ['username'])]
#[ORM\UniqueConstraint(name: 'port', columns: ['port'])]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface, CacheKeyGetterInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(type: 'string', length: 32, nullable: false, options: ['collation' => 'utf8mb4_bin'])]
    private string $username;

    private ?string $plainPassword = null;

    #[ORM\Column(type: 'text', nullable: false, options: ['collation' => 'utf8mb4_bin'])]
    private string $password;

    #[ORM\Column(type: 'string', length: 32, nullable: false, options: ['collation' => 'utf8mb4_bin', 'fixed' => true])]
    private string $salt;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private DateTime $creationDateTime;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $connectionDateTime = null;

    /**
     * @var UserHasRole[]|Collection
     */
    #[ORM\OneToMany(targetEntity: 'UserHasRole', mappedBy: 'user', cascade: ['persist'], fetch: 'EAGER')]
    private array|Collection $hasRoles;

    /**
     * @var Sharing[]|Collection
     */
    #[ORM\OneToMany(targetEntity: 'Sharing', mappedBy: 'user', indexBy: 'token')]
    private array|Collection $sharings;

    #[ORM\OneToOne(targetEntity: TrackedProcess::class, fetch: 'LAZY')]
    private ?TrackedProcess $athorrentProcess = null;

    #[ORM\Column(type: 'integer')]
    private int $port;

    /**
     * @param string[] $roles
     */
    public function __construct($username, $plainPassword, $salt, array $roles)
    {
        $this->username = $username;
        $this->plainPassword = $plainPassword;
        $this->salt = $salt;
        $this->creationDateTime = new DateTime();

        $this->hasRoles = array_map(fn($role) => new UserHasRole($this, $role), $roles);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;

        // We reset the password, when setting the password as plain text
        // to trigger the preUpdate doctrine event
        $this->password = '';
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
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
        if ($this->connectionDateTime instanceof DateTime) {
            return $this->connectionDateTime->getTimestamp();
        }

        return 0;
    }

    public function setConnectionDateTime(DateTime $dateTime): void
    {
        $this->connectionDateTime = $dateTime;
    }

    /**
     * @return UserHasRole[]|Collection
     */
    public function getHasRoles(): array|Collection
    {
        return $this->hasRoles;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        $roles = [];

        foreach ($this->hasRoles as $hasRole) {
            $roles[] = $hasRole->getRole();
        }

        return $roles;
    }

    /**
     * @return Sharing[]|Collection
     */
    public function getSharings(): array|Collection
    {
        return $this->sharings;
    }

    public function getAthorrentProcess(): ?TrackedProcess
    {
        return $this->athorrentProcess;
    }

    public function setAthorrentProcess(?TrackedProcess $process): void
    {
        $this->athorrentProcess = $process;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function setPort(int $port): void
    {
        $this->port = $port;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getCacheKey(): string
    {
        return (string)$this->id;
    }

    public function getPath(string $path): string
    {
        return Path::join(USER_DIR, $this->id, $path);
    }

    public function getBackendPath(string $path = ''): string
    {
        return $this->getPath(Path::join('backend', $path));
    }
}
