<?php

namespace Athorrent\Utils;

class MimeType
{
    public static function isText($mimeType)
    {
        return strpos($mimeType, "text/") === 0;
    }
    
    public static function isImage($mimeType)
    {
        return strpos($mimeType, "image/") === 0;
    }
    
    public static function isAudio($mimeType)
    {
        return strpos($mimeType, "audio/") === 0;
    }
    
    public static function isVideo($mimeType)
    {
        return strpos($mimeType, "video/") === 0;
    }
    
    public static function isPdf($mimeType)
    {
        return strpos($mimeType, "application/pdf") === 0;
    }
    
    public static function isArchive($mimeType)
    {
        return strpos($mimeType, 'application/zip') === 0 || strpos($mimeType, 'application/x-gzip') === 0;
    }
    
    public static function isPlayable($mimeType)
    {
        return strpos($mimeType, "audio/mpeg") === 0 || strpos($mimeType, "video/mp4") === 0;
    }
    
    public static function isDisplayable($mimeType)
    {
        return self::isText($mimeType) || self::isImage($mimeType);
    }
}
