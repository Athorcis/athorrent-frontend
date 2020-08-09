<?php

namespace Athorrent\Database\Entity;

use Athorrent\Cache\KeyGenerator\CacheKeyGetterInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Athorrent\Database\Repository\SharingRepository")
 * @ORM\Table(indexes={@ORM\Index(columns={"creation_date_time"})})
 */
class Sharing implements CacheKeyGetterInterface
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", length=32, options={"collation": "utf8mb4_bin", "fixed": true})
     */
    private $token;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="sharings")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $user;

    /**
     * @var string
     * @ORM\Column(type="text", options={"collation": "utf8mb4_bin"})
     */
    private $path;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    private $creationDateTime;

    public function __construct($user = null, $path = null)
    {
        if ($user !== null) {
            $this->token = self::generateToken($user, $path);
            $this->user = $user;
            $this->path = $path;
            $this->creationDateTime = new \DateTime();
        }
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getPath()
    {
        return $this->path;
    }

    public static function generateToken(User $user, $path)
    {
        return md5($user->getId() . '/' . $path);
    }

    public function getCacheKey(): string
    {
        return $this->token;
    }
}
