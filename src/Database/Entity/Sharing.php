<?php

namespace Athorrent\Database\Entity;

use Athorrent\Cache\KeyGenerator\CacheKeyGetterInterface;
use Athorrent\Database\Repository\SharingRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table]
#[ORM\Index(columns: ['creation_date_time'])]
#[ORM\Entity(repositoryClass: SharingRepository::class)]
class Sharing implements CacheKeyGetterInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 32, options: ['collation' => 'utf8mb4_bin', 'fixed' => true])]
    private string $token;

    #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'sharings')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'text', options: ['collation' => 'utf8mb4_bin'])]
    private string $path;

    #[ORM\Column(type: 'datetime')]
    private DateTime $creationDateTime;

    public function __construct(User $user, string $path)
    {
        $this->token = self::generateToken($user, $path);
        $this->user = $user;
        $this->path = $path;
        $this->creationDateTime = new DateTime();
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

    public function getCreationDateTime(): DateTime
    {
        return $this->creationDateTime;
    }

    public static function generateToken(User $user, $path): string
    {
        return md5($user->getId() . '/' . $path);
    }
}
