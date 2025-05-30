<?php

namespace Athorrent\Database\Entity;

use Athorrent\Cache\KeyGenerator\CacheKeyGetterInterface;
use Athorrent\Database\Repository\UserRepository;
use Athorrent\Database\Type\UserRole;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Cache;
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
#[Cache(usage: 'NONSTRICT_READ_WRITE')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, CacheKeyGetterInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[NotBlank]
    #[Length(max: 32)]
    #[ORM\Column(length: 32)]
    private string $username;

    #[ORM\Column(type: 'text')]
    private string $password;

    #[ORM\Column(length: 32, nullable: true, options: ['fixed' => true])]
    private string $salt;

    #[ORM\Column]
    private DateTimeImmutable $creationDateTime;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $connectionDateTime = null;

    /**
     * @var UserHasRole[]|Collection
     */
    #[ORM\OneToMany(targetEntity: 'UserHasRole', mappedBy: 'user', cascade: ['persist', 'detach'], fetch: 'EAGER')]
    #[Cache(usage: 'READ_ONLY')]
    private array|Collection $hasRoles;

    /**
     * @var Sharing[]|Collection
     */
    #[ORM\OneToMany(targetEntity: 'Sharing', mappedBy: 'user', indexBy: 'token', cascade: ['detach'], fetch: 'LAZY')]
    #[Cache(usage: 'NONSTRICT_READ_WRITE')]
    private array|Collection $sharings;

    #[ORM\Column]
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
            $roles[] = $hasRole->getRole()->value;
        }

        return $roles;
    }

    /**
     * @param string[]|UserRole[] $roles
     */
    public function setRoles(array $roles): void
    {
        $this->hasRoles = array_map(fn($role) => new UserHasRole($this, UserRole::fromOrSelf($role)), $roles);
    }

    /**
     * @return Sharing[]|Collection
     */
    public function getSharings(): array|Collection
    {
        return $this->sharings;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function setPort(int $port): void
    {
        $this->port = $port;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    public function getCacheKey(): string
    {
        return (string)$this->id;
    }

    public function getPath(string $path): string
    {
        return Path::join(USER_ROOT_DIR, $this->id, $path);
    }

    public function getBackendPath(string $path = ''): string
    {
        return $this->getPath(Path::join('backend', $path));
    }

    public function getFilesPath(): string
    {
        return $this->getBackendPath('files');
    }

    public function getNewTorrentsPath(): string
    {
        return $this->getBackendPath('new-torrents');
    }

    public static function as(mixed $user): static
    {
        assert(
            $user instanceof static,
            sprintf('$user should be an instance of %s found %s instead', static::class, is_object($user) ? get_class($user) : gettype($user)),
        );

        return $user;
    }
}
