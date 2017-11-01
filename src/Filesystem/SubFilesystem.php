<?php

namespace Athorrent\Filesystem;

use FilesystemIterator;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;

class SubFilesystem extends Filesystem
{
    private $root;

    private $rootDir;

    protected $writable;

    public function __construct($root, $writable)
    {
        if ($root[strlen($root) - 1] === DIRECTORY_SEPARATOR) {
            $root = substr($root, 0, -1);
        }

        $this->root = $root;
        $this->writable = $writable;

        if (is_file($root)) {
            $this->rootDir = dirname($root);
        } else {
            $this->rootDir = $root;
        }
    }

    public function getAbsolutePath($path)
    {
        $absolutePath = realpath($this->rootDir . '/' . str_replace('/', DIRECTORY_SEPARATOR, $path));

        if (!$absolutePath || strrpos($absolutePath, $this->root, -strlen($absolutePath)) === false) {
            if (empty($path)) {
                $absolutePath = $this->root;
            } else {
                throw new FileNotFoundException(null, 0, null, $path);
            }
        }

        return $absolutePath;
    }

    public function getRelativePath($absolutePath)
    {
        $relativePath = str_replace($this->rootDir, '', $absolutePath);

        if (strlen($relativePath) > 0 && $relativePath[0] === DIRECTORY_SEPARATOR) {
            $relativePath = substr($relativePath, 1);
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
    }

    public function isRoot($path)
    {
        $absolutePath = $this->getAbsolutePath($path);
        return $absolutePath === $this->root;
    }

    public function isWritable()
    {
        return $this->writable;
    }

    public function getMimeType($file)
    {
        return parent::getMimeType($this->getAbsolutePath($file));
    }

    public function remove($files)
    {
        if ($files instanceof \Traversable) {
            $files = iterator_to_array($files, false);
        } elseif (!is_array($files)) {
            $files = array($files);
        }

        foreach ($files as &$file) {
            $file = $this->getAbsolutePath($file);
        }

        $this->doRemove($files);
    }

    protected function doRemove($files)
    {
        if ($files instanceof \Traversable) {
            $files = iterator_to_array($files, false);
        } elseif (!is_array($files)) {
            $files = array($files);
        }

        $files = array_reverse($files);

        foreach ($files as $file) {
            if (is_link($file)) {
                // See https://bugs.php.net/52176
                if (!@(unlink($file) || '\\' !== DIRECTORY_SEPARATOR || rmdir($file)) && file_exists($file)) {
                    $error = error_get_last();
                    throw new IOException(sprintf('Failed to remove symlink "%s": %s.', $file, $error['message']));
                }
            } elseif (is_dir($file)) {
                $this->doRemove(new \FilesystemIterator($file, FilesystemIterator::CURRENT_AS_PATHNAME | FilesystemIterator::SKIP_DOTS));

                if (!@rmdir($file) && file_exists($file)) {
                    $error = error_get_last();
                    throw new IOException(sprintf('Failed to remove directory "%s": %s.', $file, $error['message']));
                }
            } elseif (!@unlink($file) && file_exists($file)) {
                $error = error_get_last();
                throw new IOException(sprintf('Failed to remove file "%s": %s.', $file, $error['message']));
            }
        }
    }
}
