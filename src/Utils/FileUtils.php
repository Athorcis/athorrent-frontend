<?php

namespace Athorrent\Utils;

class FileUtils {
    public static function getMimeType($path) {
        $finfo = new \finfo(FILEINFO_MIME);
        return $finfo->file($path);
    }

    public static function dirsize($path) {
        $path .= DIRECTORY_SEPARATOR;
        $dir = opendir($path);
        $size = 0;

        while ($entry = readdir($dir)) {
            if ($entry != '.' && $entry != '..') {
                $entryPath = $path . $entry;

                if (is_dir($entryPath)) {
                    $size += self::dirsize($entryPath);
                } else {
                    $size += filesize($entryPath);
                }
            }
        }

        closedir($dir);

        return $size;
    }

    public static function rrmdir($path) {
        $path .= DIRECTORY_SEPARATOR;
        $dir = opendir($path);
        $result = true;

        while ($entry = readdir($dir)) {
            if ($entry != '.' && $entry != '..') {
                $entryPath = $path . $entry;

                if (is_dir($entryPath)) {
                    if (!self::rrmdir($entryPath)) {
                        $result = false;
                    }
                } else {
                    if (!unlink($entryPath)) {
                        $result = false;
                    }
                }
            }
        }

        closedir($dir);

        if ($result) {
            rmdir($path);
        }

        return $result;
    }

    public static function encodeFilename($filename) {
        $parts = pathinfo($filename);
        return base64_encode($parts['filename']) . $parts['extension'];
    }
}

?>
