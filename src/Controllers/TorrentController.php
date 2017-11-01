<?php

namespace Athorrent\Controllers;

use Athorrent\Routing\AbstractController;
use Athorrent\Utils\ServiceUnvailableException;
use Athorrent\Utils\TorrentManager;
use Athorrent\View\View;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class TorrentController extends AbstractController
{
    protected function getRouteDescriptors()
    {
        return [
            ['GET', '/', 'listTorrents', 'both'],
            ['GET', '/magnet', 'addMagnet'],

            ['GET', '/trackers/{hash}', 'listTrackers', 'ajax'],

            ['POST', '/files', 'uploadTorrent', 'ajax'],
            ['POST', '/', 'addTorrents', 'ajax'],

            ['POST', '/pause/{hash}', 'pauseTorrent', 'ajax'],
            ['POST', '/resume/{hash}', 'resumeTorrent', 'ajax'],
            ['POST', '/remove/{hash}', 'removeTorrent', 'ajax']
        ];
    }

    protected function getTorrentManager(Application $app)
    {
        return TorrentManager::getInstance($app['user']->getUserId());
    }

    public function listTorrents(Application $app, Request $request)
    {
        $torrentManager = $this->getTorrentManager($app);

        try {
            $torrents = $torrentManager->getTorrents();
            $clientUpdating = false;

            usort(
                $torrents, function ($a, $b) {
                    return strcmp($a['name'], $b['name']);
                }
            );
        } catch (ServiceUnvailableException $e) {
            $torrents = array();
            $clientUpdating = true;
        }

        return new View([
            'torrents' => $torrents,
            'client_updating' => $clientUpdating,
            '_templates' => ['dropzonePreview'],
            '_strings' => ['torrents.dropzone']
        ]);
    }

    public function listTrackers(Application $app, Request $request, $hash)
    {
        $torrentManager = $this->getTorrentManager($app);
        $trackers = $torrentManager->listTrackers($hash);

        return new View(['trackers' => $trackers]);
    }

    public function uploadTorrent(Application $app, Request $request)
    {
        $torrentManager = $this->getTorrentManager($app);
        $file = $request->files->get('upload-torrent-file');

        if ($file && $file->getClientSize() <= 1048576) {
            if ($file->getMimeType() === 'application/x-bittorrent') {
                $file->move($torrentManager->getTorrentsDirectory(), $file->getClientOriginalName());

                return [];
            } else {
                $app->abort(500, 'error.notATorrent');
            }
        }

        $app->abort(500, 'error.fileTooBig');
    }

    public function addMagnet(Application $app, Request $request)
    {
        $torrentManager = $this->getTorrentManager($app);
        $magnet = $request->query->get('magnet');

        if ($magnet) {
            $torrentManager->addTorrentFromMagnet($magnet);
        }

        return $app->redirect('listTorrents');
    }

    public function addTorrents(Application $app, Request $request)
    {
        $torrentManager = $this->getTorrentManager($app);

        $files = $request->request->get('add-torrent-files');
        $magnets = $request->request->get('add-torrent-magnets');

        $torrentsDir = $torrentManager->getTorrentsDirectory() . DIRECTORY_SEPARATOR;

        if ($files) {
            foreach ($files as $file) {
                $torrentPath = $torrentsDir . $file;

                if (file_exists($torrentPath)) {
                    $torrentManager->addTorrentFromFile($torrentPath);
                }
            }
        }

        if ($magnets) {
            foreach ($magnets as $magnet) {
                $torrentManager->addTorrentFromMagnet($magnet);
            }
        }

        return [];
    }

    public function pauseTorrent(Application $app, Request $request, $hash)
    {
        $torrentManager = $this->getTorrentManager($app);
        $torrentManager->pauseTorrent($hash);
        return [];
    }

    public function resumeTorrent(Application $app, Request $request, $hash)
    {
        $torrentManager = $this->getTorrentManager($app);
        $torrentManager->resumeTorrent($hash);
        return [];
    }

    public function removeTorrent(Application $app, Request $request, $hash)
    {
        $torrentManager = $this->getTorrentManager($app);
        $torrentManager->removeTorrent($hash);
        return [];
    }
}
