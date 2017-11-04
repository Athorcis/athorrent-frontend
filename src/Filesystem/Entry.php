<?php

namespace Athorrent\Filesystem;

use Athorrent\Database\Entity\Sharing;
use Athorrent\Database\Entity\User;

class Entry
{
    private $absolutePath;

    private $relativePath;

    private $user;

    private $cachable;

    private $deletable;

    private $sharable;

    private $file;

    private $modificationTime;

    private $encodedPath;

    private $mimeType;

    private $icon;

    private $name;

    private $size;

    private $playable;

    private $displayable;

    private $sharingToken;

    public function __construct($absolutePath, $relativePath, User $user, $cachable, $deletable, $sharable)
    {
        $this->absolutePath = $absolutePath;
        $this->relativePath = $relativePath;
        $this->user = $user;
        $this->cachable = $cachable;
        $this->deletable = $deletable;
        $this->sharable = $sharable;
    }

    public function getRelativePath()
    {
        return $this->relativePath;
    }

    public function getAbsolutePath()
    {
        return $this->absolutePath;
    }

    public function isCachable()
    {
        return $this->cachable;
    }

    public function isDeletable()
    {
        return $this->deletable;
    }

    public function isSharable()
    {
        return $this->sharable;
    }

    public function isFile()
    {
        if ($this->file === null) {
            $this->file = is_file($this->absolutePath);
        }

        return $this->file;
    }

    public function getModificationTime()
    {
        if ($this->modificationTime === null) {
            $this->modificationTime = filemtime($this->absolutePath);
        }

        return $this->modificationTime;
    }

    public function getEncodedPath()
    {
        if ($this->encodedPath === null) {
            $this->encodedPath = base64_encode($this->relativePath);
        }

        return $this->encodedPath;
    }

    public function getMimeType()
    {
        if ($this->mimeType === null) {
            $this->mimeType = FileUtils::getMimeType($this->absolutePath);
        }

        return $this->mimeType;
    }

    public function getIcon()
    {
        if ($this->icon === null) {
            $mimeType = $this->getMimeType();

            if ($mimeType === 'directory') {
                $this->icon = 'fa-folder-open-o';
            } elseif (MimeType::isText($mimeType)) {
                $this->icon = 'fa-file-text-o';
            } elseif (MimeType::isImage($mimeType)) {
                $this->icon = 'fa-file-image-o';
            } elseif (MimeType::isAudio($mimeType)) {
                $this->icon = 'fa-file-audio-o';
            } elseif (MimeType::isVideo($mimeType)) {
                $this->icon = 'fa-file-video-o';
            } elseif (MimeType::isPdf($mimeType)) {
                $this->icon = 'fa-file-pdf-o';
            } elseif (MimeType::isArchive($mimeType)) {
                $this->icon = 'fa-file-archive-o';
            } else {
                $this->icon = 'fa-file-o';
            }
        }

        return $this->icon;
    }

    public function getName()
    {
        if ($this->name === null) {
            $this->name = basename($this->relativePath);
        }

        return $this->name;
    }

    public function getSize()
    {
        if ($this->size === null) {
            if ($this->isFile()) {
                $this->size = filesize($this->absolutePath);
            } else {
                $this->size = FileUtils::dirsize($this->absolutePath);
            }
        }

        return $this->size;
    }

    public function isPlayable()
    {
        if ($this->playable === null) {
            $this->playable = MimeType::isPlayable($this->getMimeType());
        }

        return $this->playable;
    }

    public function isDisplayable()
    {
        if ($this->displayable === null) {
            $this->displayable = MimeType::isDisplayable($this->getMimeType());
        }

        return $this->displayable;
    }

    public function isShared()
    {
        if ($this->isSharable()) {
            return isset($this->user->getSharings()[$this->getSharingToken()]);
        }

        return false;
    }

    public function getSharingToken()
    {
        if ($this->sharingToken === null) {
            $path = $this->relativePath;

            if (!$this->isFile()) {
                $path .= '/';
            }

            $this->sharingToken = Sharing::generateToken($this->user, $this->relativePath);
        }

        return $this->sharingToken;
    }

    public static function compare(self $entry, self $other)
    {
        if (!$entry->isFile() && $other->isFile()) {
            return -1;
        } elseif ($entry->isFile() && !$other->isFile()) {
            return 1;
        }

        return strcmp($entry->getName(), $other->getName());
    }
}
