<?php

namespace Athorrent\Utils;

class CacheUtils
{
    public static function clearApc()
    {
        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
        }

        return true;
    }

    protected static function clearCacheDir($path)
    {
        if (is_dir($path)) {
            return FileUtils::rrmdir($path);
        }

        return true;
    }

    public static function clearTwig()
    {
        return self::clearCacheDir(CACHE_DIR . DIRECTORY_SEPARATOR . 'twig');
    }

    public static function clearTranslations()
    {
        return self::clearCacheDir(CACHE_DIR . DIRECTORY_SEPARATOR . 'translator');
    }
}