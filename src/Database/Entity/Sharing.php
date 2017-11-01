<?php

namespace Athorrent\Database\Entity;

use DateTime;

/**
 *  @Entity(repositoryClass="Athorrent\Database\Repository\SharingRepository")
 *  @Table(indexes={@Index(columns={"creationDateTime"})})
 */
class Sharing
{
    /**
     *  @Id
     *  @Column(type="string", length=32, options={"collation": "utf8_bin", "fixed": true})
     */
    private $token;

    /**
     *  @ManyToOne(targetEntity="User", inversedBy="sharings")
     *  @JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $user;

    /**
     *  @Column(type="text", options={"collation": "utf8_bin"})
     */
    private $path;

    /**
     *  @Column(type="datetime")
     */
    private $creationDateTime;

    public function __construct($user = null, $path = null)
    {
        if ($user !== null) {
            $this->token = self::generateToken($user, $path);
            $this->user = $user;
            $this->path = $path;
            $this->creationDateTime = new DateTime();
        }
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getUser()
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
}
