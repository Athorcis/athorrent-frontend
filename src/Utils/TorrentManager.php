<?php

namespace Athorrent\Utils;

use Athorrent\Database\Entity\User;
use Athorrent\Filesystem\FileUtils;

class TorrentManager
{
    private $user;

    private $service;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->service = new AthorrentService($user->getId());
    }

    public function getTorrentsDirectory()
    {
        return TORRENTS_DIR . DIRECTORY_SEPARATOR . $this->user->getId();
    }

    public function addTorrentFromUrl($url)
    {
        $path = $this->getTorrentsDirectory() . DIRECTORY_SEPARATOR . md5($url) . '.torrent';

        file_put_contents($path, file_get_contents($url));

        return $this->addTorrentFromFile($path);
    }

    public function addTorrentFromFile($path)
    {
        $oldFile = realpath($path);
        $newFile = FileUtils::encodeFilename($oldFile);

        rename($oldFile, $newFile);

        $result = $this->service->call('addTorrentFromFile', ['file' => $newFile]);
        unlink($newFile);

        return $result;
    }

    public function addTorrentFromMagnet($magnet)
    {
        return $this->service->call('addTorrentFromMagnet', ['magnet' => $magnet]);
    }

    public function getTorrents()
    {
        return $this->service->call('getTorrents');
    }

    public function getPaths()
    {
        $paths = $this->service->call('getPaths');

        if (DIRECTORY_SEPARATOR !== '/') {
            for ($i = 0, $size = count($paths); $i < $size; ++$i) {
                $paths[$i] = str_replace('/', DIRECTORY_SEPARATOR, $paths[$i]);
            }
        }

        return $paths;
    }

    public function pauseTorrent($hash)
    {
        return $this->service->call('pauseTorrent', ['hash' => $hash]);
    }

    public function resumeTorrent($hash)
    {
        return $this->service->call('resumeTorrent', ['hash' => $hash]);
    }

    public function removeTorrent($hash)
    {
        return $this->service->call('removeTorrent', ['hash' => $hash]);
    }

    public function listTrackers($hash)
    {
        return $this->service->call('listTrackers', ['hash' => $hash]);
    }
}
