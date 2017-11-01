<?php

namespace Athorrent\Filesystem;

use ArrayObject;
use FilesystemIterator;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem as BaseFilesystem;
use Traversable;

class Filesystem extends BaseFilesystem
{
    public function getSize($files)
    {
        if (!$files instanceof Traversable) {
            $files = new ArrayObject(is_array($files) ? $files : [$files]);
        }

        $size = 0;

        foreach ($files as $file) {

            if (is_dir($file)) {
                $size += $this->getSize(new FilesystemIterator(
                    $file,
                    FilesystemIterator::CURRENT_AS_PATHNAME | FilesystemIterator::SKIP_DOTS
                ));
            } else {
                $tmp = @filesize($file);

                if ($tmp === false) {
                    $error = error_get_last();
                    throw new IOException(sprintf('Failed to retrieve size of "%s": %s.', $file, $error['message']));
                }

                $size += $tmp;
            }
        }

        return $size;
    }

    public function getMimeType($path)
    {
        $finfo = new \finfo(FILEINFO_MIME);
        return $finfo->file($path);
    }

    public function encodeFilename($path)
    {
        $parts = pathinfo($path);
        return $parts['dirname'] . DIRECTORY_SEPARATOR . base64_encode($parts['filename']) . '.' . $parts['extension'];
    }
}
