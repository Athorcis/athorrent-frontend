<?php

namespace Athorrent\Utils;

use Athorrent\Entity\Sharing;

class FileManager {
    private $ownerId;

    private $root;

    private $writable;

    private $rootDir;

    private $torrentPaths;

    private function __construct($ownerId, $root, $writable) {
        if ($root[strlen($root) - 1] === DIRECTORY_SEPARATOR) {
            $root = substr($root, -1);
        }

        $this->ownerId = $ownerId;
        $this->root = $root;
        $this->writable = $writable;

        if (is_file($this->root)) {
            $this->rootDir = dirname($this->root);
        } else {
            $this->rootDir = $this->root;
        }
    }

    public function getOwnerId() {
        return $this->ownerId;
    }

    public function isOwner($userId) {
        return $this->ownerId === $userId;
    }

    public function isRoot($path) {
        return $this->root === $path;
    }

    public function getAbsolutePath($path) {
        $root = $this->root . DIRECTORY_SEPARATOR;
        $absolutePath = realpath($root . str_replace('/', DIRECTORY_SEPARATOR, $path));

        if (!$absolutePath || strrpos($absolutePath, $root, -strlen($absolutePath)) === false) {
            $absolutePath = $this->root;
        }

        return $absolutePath;
    }

    public function getRelativePath($absolutePath) {
        $relativePath = str_replace($this->rootDir, '', $absolutePath);

        if (strlen($relativePath) > 0 && $relativePath[0] === DIRECTORY_SEPARATOR) {
            $relativePath = substr($relativePath, 1);
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
    }

    public function getTorrentPaths() {
        if ($this->torrentPaths === null) {
            $torrentManager = TorrentManager::getInstance($this->ownerId);
            $this->torrentPaths = $torrentManager->getPaths();
        }

        return $this->torrentPaths;
    }

    public function isTorrent($path) {
        $torrentPaths = $this->getTorrentPaths();
        $index = -strlen($path);

        foreach ($torrentPaths as $torrentPath) {
            if (strrpos($path, $torrentPath, $index) !== false) {
                return true;
            }
        }

        return false;
    }

    public function containsTorrents($path) {
        $torrentPaths = $this->getTorrentPaths();

        foreach ($torrentPaths as $torrentPath) {
            if (strrpos($torrentPath, $path, -strlen($torrentPath)) !== false) {
                return true;
            }
        }

        return false;
    }

    public function isWritable() {
        return $this->writable;
    }

    public function isDeletable($absolutePath) {
        return $this->isWritable() && !$this->isTorrent($absolutePath);
    }

    public function listEntries($absolutePath) {
        $writable = $this->isWritable();
        $sharable = $writable;

        $dirIsTorrent = $this->isTorrent($absolutePath);
        $dirDeletable = $writable && !$dirIsTorrent;

        $dirContainsTorrents = $this->containsTorrents($absolutePath);

        $name = basename($absolutePath);
        $entries = array();

        if (is_dir($absolutePath)) {
            $relativePath = $this->getRelativePath($absolutePath);
            $size = FileUtils::dirsize($absolutePath);
            $dir = opendir($absolutePath);

            while ($entry = readdir($dir)) {
                if ($entry != '.' && ($entry != '..' || $relativePath != '')) {
                    $entries[] = $entry;
                }
            }

            closedir($dir);
        } else {
            $size = filesize($absolutePath);
            $entries[] = basename($absolutePath);
            $absolutePath = dirname($absolutePath);
            $relativePath = $this->getRelativePath($absolutePath);
        }

        $result = array(
            'name' => $name,
            'size' => $size,
            'files' => array()
        );

        $absolutePath .= DIRECTORY_SEPARATOR;
        
        if ($relativePath != '') {
            $relativePath .= '/';
        }
        
        foreach ($entries as $entry) {
            $absoluteEntryPath = $absolutePath . $entry;

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

            $result['files'][] = new File($absoluteEntryPath, $relativePath . $entry, $this->ownerId, $cachable, $deletable, $sharable);
        }

        usort($result['files'], function (File $a, File $b) {
            if (!$a->isFile() && $b->isFile()) {
                return -1;
            } else if ($a->isFile() && !$b->isFile()) {
                return 1;
            }

            return strcmp($a->getName(), $b->getName());
        });

        return $result;
    }

    public function remove($path) {
        if (!$this->isDeletable($path)) {
            return false;
        }

        Sharing::deleteByPathRecursively($this->getRelativePath($path), $this->ownerId);

        if (is_file($path)) {
            return unlink($path);
        } else if ($path != $this->rootDir) {
            return FileUtils::rrmdir($path);
        }

        return false;
    }

    private static $instances;

    public static function getInstance($ownerId, $userId = -1, $pathSuffix = '', $writable = true) {
        $root = implode(DIRECTORY_SEPARATOR, array(BIN, 'files', $ownerId));

        if ($userId === -1) {
            $userId = $ownerId;
        } else {
            $writable = $writable && $ownerId === $userId;
        }

        if (strlen($pathSuffix) > 0) {
            $root .= DIRECTORY_SEPARATOR . $pathSuffix;
        }

        if (!isset(self::$instances[$root])) {
            self::$instances[$root] = new FileManager($ownerId, $root, $writable);
        }

        return self::$instances[$root];
    }

    public static function getByUser($userId) {
        return self::getInstance($userId);
    }

    public static function getBySharing($userId, Sharing $sharing) {
        return self::getInstance($sharing->getUserId(), $userId, str_replace('/', DIRECTORY_SEPARATOR, $sharing->getPath()), false);
    }
}

?>
