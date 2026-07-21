<?php

declare(strict_types=1);

namespace Athorrent\Filesystem;

use FilesystemIterator;
use Symfony\Component\Filesystem\Exception\IOException;

class FileUtils extends \Symfony\Component\Filesystem\Filesystem
{
    /**
     * @param string|iterable<string> $files
     */
    public function getSize(string|iterable $files): int
    {
        $size = 0;
        $files = $this->toIterable($files);

        foreach ($files as $file) {
            if (is_dir($file)) {
                /** @var iterable<string> $iterator */
                $iterator = new FilesystemIterator($file, FilesystemIterator::CURRENT_AS_PATHNAME | FilesystemIterator::SKIP_DOTS);
                $size += $this->getSize($iterator);
            } else {
                $bytes = @filesize($file);

                if ($bytes === false) {
                    $error = error_get_last();
                    throw new IOException(sprintf(
                        'Failed to get size of file "%s": %s.',
                        $file,
                        $error['message'] ?? 'unknown error',
                    ));
                }

                $size += $bytes;
            }
        }

        return $size;
    }

    /**
     * @param string|iterable<string> $dirs
     */
    public function mkdirAs(string|iterable $dirs, int|string $user, int $mode = 0o777): void
    {
        $this->mkdir($dirs, $mode);

        if (is_int($user)) {
            $chown = posix_getuid() !== $user;
        }
        else {
            $userInfo = posix_getpwuid(posix_getuid());

            if ($userInfo === false) {
                throw new \RuntimeException("user $user doesn't exist");
            }

            $chown = $userInfo['name'] !== $user;
        }

        if ($chown) {
            $this->chown($dirs, $user);
        }
    }

    /**
     * @param string|iterable<string> $files
     * @return iterable<string>
     */
    private function toIterable(string|iterable $files): iterable
    {
        return is_iterable($files) ? $files : [$files];
    }
}
