<?php

namespace Athorrent\Filesystem;

use Athorrent\Database\Entity\User;
use Athorrent\Utils\TorrentManager;
use FilesystemIterator;
use Silex\Application;

class UserFilesystem extends SubFilesystem
{
    private $user;

    private $torrentManager;

    private $torrentPaths;

    public function __construct(Application $app, User $user, $path = '', $writable = true)
    {
        $this->user = $user;
        $this->torrentManager = $app['torrent_manager']($user);

        parent::__construct(FILES_DIR . DIRECTORY_SEPARATOR . $user->getId() . DIRECTORY_SEPARATOR . $path, $writable);
    }

    public function getUser()
    {
        return $this->user;
    }

    protected function getTorrentPaths()
    {
        if ($this->torrentPaths === null) {
            $this->torrentPaths = $this->torrentManager->getPaths();
        }

        return $this->torrentPaths;
    }

    protected function isTorrent($path)
    {
        $torrentPaths = $this->getTorrentPaths();
        $index = -strlen($path);

        foreach ($torrentPaths as $torrentPath) {
            if (strrpos($path, $torrentPath, $index) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function containsTorrents($path)
    {
        $torrentPaths = $this->getTorrentPaths();

        foreach ($torrentPaths as $torrentPath) {
            if (strrpos($torrentPath, $path, -strlen($torrentPath)) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function isDeletable($path)
    {
        return $this->writable && !$this->isTorrent($path);
    }

    protected function doRemove($files)
    {
        if ($files instanceof \Traversable) {
            $files = iterator_to_array($files, false);
        } elseif (!is_array($files)) {
            $files = array($files);
        }

        $removableFiles = [];

        foreach ($files as $file) {
            if (!$this->isTorrent($file)) {
                $removableFiles[] = $file;
            }
        }

        parent::doRemove($removableFiles);

        return count($removableFiles) > 0;
    }

    public function list($path)
    {
        $absolutePath = $this->getAbsolutePath($path);

        $dirIsTorrent = $this->isTorrent($absolutePath);
        $dirDeletable = $this->writable && !$dirIsTorrent;

        $dirContainsTorrents = $this->containsTorrents($absolutePath);

        if (is_dir($absolutePath)) {
            $relativePath = $this->getRelativePath($absolutePath);

            if (!empty($relativePath)) {
                $names[] = '..';
            }

            $iterator = new FilesystemIterator(
                $absolutePath,
                FilesystemIterator::KEY_AS_FILENAME | FilesystemIterator::CURRENT_AS_PATHNAME | FilesystemIterator::SKIP_DOTS
            );

            foreach ($iterator as $name => $path) {
                $names[] = $name;
            }
        } else {
            $names[] = basename($absolutePath);

            $absolutePath = dirname($absolutePath);
            $relativePath = $this->getRelativePath($absolutePath);
        }

        if (empty($names)) {
            $entries = [];
        } else {
            $absolutePath .= DIRECTORY_SEPARATOR;

            if ($relativePath != '') {
                $relativePath .= '/';
            }

            foreach ($names as $name) {
                $absoluteEntryPath = $absolutePath . $name;

                if ($dirDeletable) {
                    $cachable = $deletable = $dirContainsTorrents ? !$this->isTorrent($absoluteEntryPath) : true;
                } else {
                    if ($dirIsTorrent) {
                        $cachable = false;
                    } else {
                        $cachable = $dirContainsTorrents ? !$this->isTorrent($absoluteEntryPath) : true;
                    }

                    $deletable = false;
                }

                $entries[] = new Entry($absoluteEntryPath, $relativePath . $name, $this->user, $cachable, $deletable, $this->writable);
            }

            usort($entries, ['Athorrent\Filesystem\Entry', 'compare']);
        }

        return $entries;
    }
}
