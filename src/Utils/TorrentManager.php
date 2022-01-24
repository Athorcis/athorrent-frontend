<?php

namespace Athorrent\Utils;

use Athorrent\Database\Entity\User;
use Athorrent\Filesystem\FileUtils;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class TorrentManager
{
    private $fs;

    private $user;

    private $service;

    /**
     * TorrentManager constructor.
     * @param User $user
     * @throws Exception
     */
    public function __construct(EntityManagerInterface $em, Filesystem $fs, User $user)
    {
        $this->fs = $fs;
        $this->user = $user;
        $this->service = new AthorrentService($em, $fs, $user);
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTorrentsDirectory(): string
    {
        return $this->user->getBackendPath('new-torrents');
    }

    /**
     * S'assure que le dossier d'upload des fichiers torrents existe et retourne son chemin
     * @return string
     */
    public function ensureTorrentsDirExists(): string
    {
        $torrentsDir = $this->getTorrentsDirectory();
        $this->fs->mkdir($torrentsDir);

        return $torrentsDir;
    }

    /**
     * @param string $url
     * @return mixed
     * @throws Exception
     */
    public function addTorrentFromUrl(string $url)
    {
        $torrentsDir = $this->ensureTorrentsDirExists();
        $path = Path::join($torrentsDir, md5($url) . '.torrent');

        file_put_contents($path, file_get_contents($url));

        return $this->addTorrentFromFile($path);
    }

    /**
     * @param string $path
     * @return mixed
     * @throws Exception
     */
    public function addTorrentFromFile(string $path)
    {
        $oldFile = Path::canonicalize($path);
        $newFile = FileUtils::encodeFilename($oldFile);

        rename($oldFile, $newFile);

        $result = $this->service->call('addTorrentFromFile', ['file' => $newFile]);
        unlink($newFile);

        return $result;
    }

    /**
     * @param string $magnet
     * @return mixed
     * @throws Exception
     */
    public function addTorrentFromMagnet(string $magnet)
    {
        return $this->service->call('addTorrentFromMagnet', ['magnet' => $magnet]);
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getTorrents()
    {
        return $this->service->call('getTorrents');
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getPaths()
    {
        $paths = $this->service->call('getPaths');

        /*if (DIRECTORY_SEPARATOR !== '/') {
            foreach ($paths as $index => $path) {
                $paths[$index] = str_replace('/', DIRECTORY_SEPARATOR, $path);
            }
        }*/

        return $paths;
    }

    /**
     * @param string $hash
     * @return mixed
     * @throws Exception
     */
    public function pauseTorrent(string $hash)
    {
        return $this->service->call('pauseTorrent', ['hash' => $hash]);
    }

    /**
     * @param string $hash
     * @return mixed
     * @throws Exception
     */
    public function resumeTorrent(string $hash)
    {
        return $this->service->call('resumeTorrent', ['hash' => $hash]);
    }

    /**
     * @param string $hash
     * @return mixed
     * @throws Exception
     */
    public function removeTorrent(string $hash)
    {
        return $this->service->call('removeTorrent', ['hash' => $hash]);
    }

    /**
     * @param string $hash
     * @return mixed
     * @throws Exception
     */
    public function listTrackers(string $hash)
    {
        return $this->service->call('listTrackers', ['hash' => $hash]);
    }
}
