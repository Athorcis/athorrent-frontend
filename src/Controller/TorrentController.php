<?php

namespace Athorrent\Controller;

use Athorrent\Utils\ServiceUnavailableException;
use Athorrent\Utils\TorrentManager;
use Athorrent\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user/torrents", name="torrents")
 */
class TorrentController
{
    /**
     * @param Application $app
     * @return TorrentManager
     */
    protected function getTorrentManager(Application $app)
    {
        return $app['torrent_manager']($app['user']);
    }

    /**
     * @Method("GET")
     * @Route("/", options={"expose"=true})
     */
    public function listTorrents(Application $app)
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
        } catch (ServiceUnavailableException $e) {
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

    /**
     * @Method("GET")
     * @Route("/{hash}/trackers", options={"expose"=true})
     */
    public function listTrackers(Application $app, $hash)
    {
        $torrentManager = $this->getTorrentManager($app);
        $trackers = $torrentManager->listTrackers($hash);

        return new View(['trackers' => $trackers]);
    }

    /**
     * @Method("POST")
     * @Route("/files", options={"expose"=true})
     */
    public function uploadTorrent(Application $app, Request $request)
    {
        $torrentManager = $this->getTorrentManager($app);
        $file = $request->files->get('upload-torrent-file');

        if ($file && $file->getClientSize() <= 1048576) {
            if ($file->getMimeType() === 'application/x-bittorrent') {
                $file->move($torrentManager->getTorrentsDirectory(), $file->getClientOriginalName());

                return [];
            } else {
                throw new \Exception('error.NotATorrent');
            }
        }

        throw new \Exception('error.fileTooBig');
    }

    /**
     * @Method("GET")
     * @Route("/magnet")
     */
    public function addMagnet(Application $app, Request $request)
    {
        $torrentManager = $this->getTorrentManager($app);
        $magnet = $request->query->get('magnet');

        if ($magnet) {
            $torrentManager->addTorrentFromMagnet($magnet);
        }

        return $app->redirect('listTorrents');
    }

    /**
     * @Method("POST")
     * @Route("/", options={"expose"=true})
     */
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

    /**
     * @Method("PUT")
     * @Route("/{hash}/pause", options={"expose"=true})
     */
    public function pauseTorrent(Application $app, $hash)
    {
        $torrentManager = $this->getTorrentManager($app);
        $torrentManager->pauseTorrent($hash);
        return [];
    }

    /**
     * @Method("PUT")
     * @Route("/{hash}/resume", options={"expose"=true})
     */
    public function resumeTorrent(Application $app, $hash)
    {
        $torrentManager = $this->getTorrentManager($app);
        $torrentManager->resumeTorrent($hash);
        return [];
    }

    /**
     * @Method("DELETE")
     * @Route("/{hash}", options={"expose"=true})
     */
    public function removeTorrent(Application $app, $hash)
    {
        $torrentManager = $this->getTorrentManager($app);
        $torrentManager->removeTorrent($hash);
        return [];
    }
}
