<?php

namespace Athorrent\Controllers;

use Athorrent\Utils\ServiceUnvailableException;
use Athorrent\Utils\TorrentManager;
use Symfony\Component\HttpFoundation\Request;

class TorrentController extends AbstractController {
    protected static $actionPrefix = 'torrents_';

    protected static $routePattern = '/torrents';

    protected static function buildRoutes() {
        $routes = parent::buildRoutes();

        $routes[] = array('GET', '/', 'listTorrents');

        return $routes;
    }

    protected static function buildAjaxRoutes() {
        $routes = parent::buildAjaxRoutes();

        $routes[] = array('GET', '/', 'listTorrents');
        $routes[] = array('GET', '/trackers/{hash}', 'listTrackers');

        $routes[] = array('POST', '/files', 'uploadTorrent');
        $routes[] = array('POST', '/', 'addTorrents');

        $routes[] = array('POST', '/pause/{hash}', 'pauseTorrent');
        $routes[] = array('POST', '/resume/{hash}', 'resumeTorrent');
        $routes[] = array('POST', '/remove/{hash}', 'removeTorrent');

        return $routes;
    }

    protected function getArguments(Request $request) {
        $arguments = parent::getArguments($request);

        array_unshift($arguments, TorrentManager::getInstance($this->getUserId()));

        return $arguments;
    }

    protected function getJsVariables() {
        global $app;

        $jsVariables = parent::getJsVariables();

        $jsVariables['templates']['dropzonePreview'] = $this->renderFragment(array(), 'dropzonePreview');
        $jsVariables['locale']['torrents.dropzone'] = $app['translator']->trans('torrents.dropzone');

        return $jsVariables;
    }

    public function getTorrentsDirectory() {
        return TORRENTS . DIRECTORY_SEPARATOR . $this->getUserId();
    }

    public function listTorrents(Request $request, TorrentManager $torrentManager) {
        try {
            $torrents = $torrentManager->getTorrents();
            $clientUpdating = false;

            usort($torrents, function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
        } catch (ServiceUnvailableException $e) {
            $torrents = array();
            $clientUpdating = true;
        }

        return $this->render(array('torrents' => $torrents, 'client_updating' => $clientUpdating));
    }

    protected function listTrackers(Request $request, TorrentManager $torrentManager, $hash) {
        $trackers = $torrentManager->listTrackers($hash);

        return $this->render(array('trackers' => $trackers));
    }

    protected function uploadTorrent(Request $request, TorrentManager $torrentManager) {
        $file = $request->files->get('upload-torrent-file');

        if ($file && $file->getClientSize() <= 1048576) {
            if ($file->getMimeType() === 'application/x-bittorrent') {
                $file->move($this->getTorrentsDirectory(), $file->getClientOriginalName());

                return $this->success();
            } else {
                return $this->abort(500, 'error.notATorrent');
            }
        }

        return $this->abort(500, 'error.fileTooBig');
    }

    protected function addTorrents(Request $request, TorrentManager $torrentManager) {
        $files = $request->request->get('add-torrent-files');
        $magnets = $request->request->get('add-torrent-magnets');

        $torrentsDir = $this->getTorrentsDirectory() . DIRECTORY_SEPARATOR;

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

    protected function pauseTorrent(Request $request, TorrentManager $torrentManager, $hash) {
        $torrentManager->pauseTorrent($hash);
        return $this->success();
    }

    protected function resumeTorrent(Request $request, TorrentManager $torrentManager, $hash) {
        $torrentManager->resumeTorrent($hash);
        return $this->success();
    }

    protected function removeTorrent(Request $request, TorrentManager $torrentManager, $hash) {
        $torrentManager->removeTorrent($hash);
        return $this->success();
    }
}

?>
