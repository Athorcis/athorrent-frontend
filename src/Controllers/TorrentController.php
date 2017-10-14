<?php

namespace Athorrent\Controllers;

use Athorrent\Utils\ServiceUnvailableException;
use Athorrent\Utils\TorrentManager;
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

    protected function getJsVariables()
    {
        global $app;

        $jsVariables = parent::getJsVariables();

        $jsVariables['templates']['dropzonePreview'] = $this->renderFragment(array(), 'dropzonePreview');
        $jsVariables['locale']['torrents.dropzone'] = $app['translator']->trans('torrents.dropzone');

        return $jsVariables;
    }

    protected function getTorrentManager(Application $app)
    {
        return TorrentManager::getInstance($this->getUserId());
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

        return $this->render(array('torrents' => $torrents, 'client_updating' => $clientUpdating));
    }

    public function listTrackers(Application $app, Request $request, $hash)
    {
        $torrentManager = $this->getTorrentManager($app);
        $trackers = $torrentManager->listTrackers($hash);

        return $this->render(array('trackers' => $trackers));
    }

    public function uploadTorrent(Application $app, Request $request)
    {
        $torrentManager = $this->getTorrentManager($app);
        $file = $request->files->get('upload-torrent-file');

        if ($file && $file->getClientSize() <= 1048576) {
            if ($file->getMimeType() === 'application/x-bittorrent') {
                $file->move($torrentManager->getTorrentsDirectory(), $file->getClientOriginalName());

                return $this->success();
            } else {
                return $this->abort(500, 'error.notATorrent');
            }
        }

        return $this->abort(500, 'error.fileTooBig');
    }

    public function addMagnet(Application $app, Request $request)
    {
        $torrentManager = $this->getTorrentManager($app);
        $magnet = $request->query->get('magnet');

        if ($magnet) {
            $torrentManager->addTorrentFromMagnet($magnet);
        }

        return $this->redirect('listTorrents');
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

        return $this->success();
    }

    public function pauseTorrent(Application $app, Request $request, $hash)
    {
        $torrentManager = $this->getTorrentManager($app);
        $torrentManager->pauseTorrent($hash);
        return $this->success();
    }

    public function resumeTorrent(Application $app, Request $request, $hash)
    {
        $torrentManager = $this->getTorrentManager($app);
        $torrentManager->resumeTorrent($hash);
        return $this->success();
    }

    public function removeTorrent(Application $app, Request $request, $hash)
    {
        $torrentManager = $this->getTorrentManager($app);
        $torrentManager->removeTorrent($hash);
        return $this->success();
    }
}
