<?php

namespace Athorrent\Filesystem;

trait MimeTypeCheckerTrait
{
    abstract public function getMimeType();

    public function isText(): bool
    {
        return str_starts_with($this->getMimeType(), 'text/');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->getMimeType(), 'image/');
    }

    public function isAudio(): bool
    {
        return str_starts_with($this->getMimeType(), 'audio/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->getMimeType(), 'video/');
    }

    public function isPdf(): bool
    {
        return str_starts_with($this->getMimeType(), 'application/pdf');
    }

    public function isArchive(): bool
    {
        $mimeType = $this->getMimeType();
        return str_starts_with($mimeType, 'application/zip') || str_starts_with($mimeType, 'application/x-gzip');
    }

    public function isPlayable(): bool
    {
        $mimeType = $this->getMimeType();
        return str_starts_with($mimeType, 'audio/mpeg') || str_starts_with($mimeType, 'video/mp4');
    }

    public function isDisplayable(): bool
    {
        return $this->isText() || $this->isImage();
    }
}
