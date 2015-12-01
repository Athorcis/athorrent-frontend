<?php

namespace Athorrent\Utils;

class CacheUtils {
    public static function clearApc() {
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache('user');
        }

        return true;
    }

    protected static function clearCacheDir($path) {
        if (is_dir($path)) {
            return FileUtils::rrmdir($path);
        }

        return true;
    }

    public static function clearTwig() {
        return self::clearCacheDir(CACHE . DIRECTORY_SEPARATOR . 'twig');
    }

    public static function clearTranslations() {
        return self::clearCacheDir(CACHE . DIRECTORY_SEPARATOR . 'translator');
    }
}

?>
