<?php

namespace Athorrent\Database\Entity;

use Athorrent\Cache\KeyGenerator\CacheKeyGetterInterface;
use Athorrent\Database\Repository\SharingRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table]
#[ORM\Index(columns: ['creation_date_time'])]
#[ORM\Index(columns: ['user_id', 'path'], options:['lengths' => [null, 50]])]
#[ORM\Entity(repositoryClass: SharingRepository::class)]
class Sharing implements CacheKeyGetterInterface
{
    #[ORM\Id]
    #[ORM\Column(length: 32, options:  ['fixed' => true])]
    private string $token;

    #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'sharings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'text')]
    private string $path;

    #[ORM\Column]
    private DateTimeImmutable $creationDateTime;

    public function __construct(User $user, string $path)
    {
        $this->token = self::generateToken($user, $path);
        $this->user = $user;
        $this->path = $path;
        $this->creationDateTime = new DateTimeImmutable();
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getCacheKey(): string
    {
        return $this->token;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getCreationDateTime(): DateTimeImmutable
    {
        return $this->creationDateTime;
    }

    public static function generateToken(User $user, $path): string
    {
        return md5($user->getId() . '/' . $path);
    }
}
