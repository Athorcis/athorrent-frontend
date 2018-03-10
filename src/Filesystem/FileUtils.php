<?php

namespace Athorrent\Filesystem;

use Symfony\Component\Filesystem\Exception\IOException;

class FileUtils extends \Symfony\Component\Filesystem\Filesystem
{
    public function getSize($files): int
    {
        $size = 0;
        $files = $this->toIterable($files);

        foreach ($files as $file) {
            if (is_dir($file)) {
                $iterator = new \FilesystemIterator($file, \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS);
                $size += $this->getSize($iterator);
            } else {
                $bytes = @filesize($file);

                if ($bytes === false) {
                    $error = error_get_last();
                    throw new IOException(sprintf('Failed to get size of file "%s": %s.', $file, $error['message']));
                }

                $size += $bytes;
            }
        }

        return $size;
    }

    private function toIterable($files): iterable
    {
        return is_array($files) || $files instanceof \Traversable ? $files : array($files);
    }

    public static function encodeFilename(string $path)
    {
        $parts = pathinfo($path);
        return $parts['dirname'] . DIRECTORY_SEPARATOR . base64_encode($parts['filename']) . '.' . $parts['extension'];
    }
}
