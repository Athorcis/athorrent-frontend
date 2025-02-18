<?php

namespace Athorrent\Database\Entity;

use Athorrent\Cache\KeyGenerator\CacheKeyGetterInterface;
use Athorrent\Database\Repository\UserRepository;
use Athorrent\Process\Entity\TrackedProcess;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'username', columns: ['username'])]
#[ORM\UniqueConstraint(name: 'port', columns: ['port'])]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['username'], message: 'error.usernameAlreadyUsed')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, CacheKeyGetterInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[NotBlank]
    #[Length(max: 32)]
    #[ORM\Column(type: 'string', length: 32, nullable: false)]
    private string $username;

    private ?string $plainPassword = null;

    #[ORM\Column(type: 'text', nullable: false)]
    private string $password;

    #[ORM\Column(type: 'string', length: 32, nullable: false, options: ['fixed' => true])]
    private string $salt;

    #[ORM\Column(type: 'datetime_immutable', nullable: false)]
    private DateTimeImmutable $creationDateTime;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $connectionDateTime = null;

    /**
     * @var UserHasRole[]|Collection
     */
    #[ORM\OneToMany(targetEntity: 'UserHasRole', mappedBy: 'user', cascade: ['persist', 'detach'], fetch: 'EAGER')]
    private array|Collection $hasRoles;

    /**
     * @var Sharing[]|Collection
     */
    #[ORM\OneToMany(targetEntity: 'Sharing', mappedBy: 'user', indexBy: 'token', cascade: ['detach'], fetch: 'LAZY')]
    private array|Collection $sharings;

    #[ORM\OneToOne(targetEntity: TrackedProcess::class, fetch: 'LAZY')]
    private ?TrackedProcess $athorrentProcess = null;

    #[ORM\Column(type: 'integer')]
    private int $port;

    public function __construct()
    {
        $this->creationDateTime = new DateTimeImmutable();
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

    public function setSalt(string $salt): void
    {
        $this->salt = $salt;
    }

    public function getCreationTimestamp(): int
    {
        return $this->creationDateTime->getTimestamp();
    }

    public function getConnectionTimestamp(): int
    {
        if ($this->connectionDateTime instanceof DateTimeImmutable) {
            return $this->connectionDateTime->getTimestamp();
        }

        return 0;
    }

    public function setConnectionDateTime(DateTimeImmutable $dateTime): void
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
     * @param string[] $roles
     */
    public function setRoles(array $roles): void
    {
        $this->hasRoles = array_map(fn($role) => new UserHasRole($this, $role), $roles);
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
