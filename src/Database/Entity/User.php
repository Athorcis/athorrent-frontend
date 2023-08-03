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
    private TrackedProcess $athorrentProcess;

    #[ORM\Column(type: 'integer')]
    private int $port;

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

    public function setUsername($username): void
    {
        $this->username = $username;
    }

    public function getPlainPassword(): ?string
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

    public function setConnectionDateTime(DateTime $dateTime): void
    {
        $this->connectionDateTime = $dateTime;
    }

    public function getHasRoles(): array|Collection
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
     * @return Sharing[]|Collection
     */
    public function getSharings(): array|Collection
    {
        return $this->sharings;
    }

    /**
     * @return TrackedProcess|null
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

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
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
