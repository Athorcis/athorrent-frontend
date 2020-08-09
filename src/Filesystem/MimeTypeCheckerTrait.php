<?php

namespace Athorrent\Filesystem;

trait MimeTypeCheckerTrait
{
    abstract public function getMimeType();

    public function isText(): bool
    {
        return strpos($this->getMimeType(), 'text/') === 0;
    }

    public function isImage(): bool
    {
        return strpos($this->getMimeType(), 'image/') === 0;
    }

    public function isAudio(): bool
    {
        return strpos($this->getMimeType(), 'audio/') === 0;
    }

    public function isVideo(): bool
    {
        return strpos($this->getMimeType(), 'video/') === 0;
    }

    public function isPdf(): bool
    {
        return strpos($this->getMimeType(), 'application/pdf') === 0;
    }

    public function isArchive(): bool
    {
        $mimeType = $this->getMimeType();
        return strpos($mimeType, 'application/zip') === 0 || strpos($mimeType, 'application/x-gzip') === 0;
    }

    public function isPlayable(): bool
    {
        $mimeType = $this->getMimeType();
        return strpos($mimeType, 'audio/mpeg') === 0 || strpos($mimeType, 'video/mp4') === 0;
    }

    public function isDisplayable(): bool
    {
        return $this->isText() || $this->isImage();
    }
}
