<?php

namespace Athorrent\Utils;

class TorrentManager
{
    private function __construct($userId)
    {
        $this->service = new AthorrentService($userId);
    }

    public function addTorrentFromFile($file)
    {
        $result = $this->service->call('addTorrentFromFile', array('file' => realpath($file)));
        unlink($file);

        return $result;
    }

    public function addTorrentFromMagnet($magnet)
    {
        return $this->service->call('addTorrentFromMagnet', array('magnet' => $magnet));
    }

    public function getTorrents()
    {
        return $this->service->call('getTorrents');
    }

    public function getPaths()
    {
        $paths =  $this->service->call('getPaths');

        if (DIRECTORY_SEPARATOR !== '/') {
            for ($i = 0, $size = count($paths); $i < $size; ++$i) {
                $paths[$i] = str_replace('/', DIRECTORY_SEPARATOR, $paths[$i]);
            }
        }

        return $paths;
    }

    public function pauseTorrent($hash)
    {
        return $this->service->call('pauseTorrent', array('hash' => $hash));
    }

    public function resumeTorrent($hash)
    {
        return $this->service->call('resumeTorrent', array('hash' => $hash));
    }

    public function removeTorrent($hash)
    {
        return $this->service->call('removeTorrent', array('hash' => $hash));
    }

    public function listTrackers($hash)
    {
        return $this->service->call('listTrackers', array('hash' => $hash));
    }

    private static $instance;

    public static function getInstance($userId)
    {
        if (!self::$instance) {
            self::$instance = new TorrentManager($userId);
        }

        return self::$instance;
    }
}
