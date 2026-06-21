<?php

declare(strict_types=1);

namespace Athorrent\Database\Entity;

use Athorrent\Cache\KeyGenerator\CacheKeyGetterInterface;
use Athorrent\Database\Repository\SharingRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Table]
#[ORM\Index(columns: ['creation_date_time'])]
#[ORM\Index(columns: ['user_id', 'path'], options:['lengths' => [null, 50]])]
#[ORM\Entity(repositoryClass: SharingRepository::class)]
class Sharing implements CacheKeyGetterInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'sharings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'text')]
    private string $path;

    #[ORM\Column]
    private DateTimeImmutable $creationDateTime;

    public function __construct(User $user, string $path)
    {
        $this->id = Uuid::v7();
        $this->user = $user;
        $this->path = $path;
        $this->creationDateTime = new DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCacheKey(): string
    {
        return $this->id->toRfc4122();
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
}
