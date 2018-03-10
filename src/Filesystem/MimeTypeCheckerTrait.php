<?php

namespace Athorrent\Filesystem;

trait MimeTypeCheckerTrait
{
    abstract public function getMimeType();

    public function isText()
    {
        return strpos($this->getMimeType(), 'text/') === 0;
    }

    public function isImage()
    {
        return strpos($this->getMimeType(), 'image/') === 0;
    }

    public function isAudio()
    {
        return strpos($this->getMimeType(), 'audio/') === 0;
    }

    public function isVideo()
    {
        return strpos($this->getMimeType(), 'video/') === 0;
    }

    public function isPdf()
    {
        return strpos($this->getMimeType(), 'application/pdf') === 0;
    }

    public function isArchive()
    {
        $mimeType = $this->getMimeType();
        return strpos($mimeType, 'application/zip') === 0 || strpos($mimeType, 'application/x-gzip') === 0;
    }

    public function isPlayable()
    {
        $mimeType = $this->getMimeType();
        return strpos($mimeType, 'audio/mpeg') === 0 || strpos($mimeType, 'video/mp4') === 0;
    }

    public function isDisplayable()
    {
        return $this->isText() || $this->isImage();
    }
}
