<?php

namespace Athorrent\Controller;

use Athorrent\Utils\ServiceUnavailableException;
use Athorrent\Utils\TorrentManager;
use Athorrent\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Route("/user/torrents", name="torrents")
 */
class TorrentController extends Controller
{
    /**
     * @Method("GET")
     * @Route("/", options={"expose"=true})
     *
     * @param TorrentManager $torrentManager
     * @return View
     *
     * @throws \Exception
     */
    public function listTorrents(TorrentManager $torrentManager)
    {
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
     *
     * @param TorrentManager $torrentManager
     * @param string $hash
     * @return View
     *
     * @throws \Exception
     */
    public function listTrackers(TorrentManager $torrentManager, string $hash)
    {
        $trackers = $torrentManager->listTrackers($hash);

        return new View(['trackers' => $trackers]);
    }

    /**
     * @Method("POST")
     * @Route("/files", options={"expose"=true})
     *
     * @param Request $request
     * @param TorrentManager $torrentManager
     * @return array
     *
     * @throws \Exception
     */
    public function uploadTorrent(Request $request, TorrentManager $torrentManager)
    {
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
     *
     * @param Request $request
     * @param TorrentManager $torrentManager
     * @return RedirectResponse
     *
     * @throws \Exception
     */
    public function addMagnet(Request $request, TorrentManager $torrentManager)
    {
        $magnet = $request->query->get('magnet');

        if ($magnet) {
            $torrentManager->addTorrentFromMagnet($magnet);
        }

        return new RedirectResponse($this->generateUrl('listTorrents', UrlGeneratorInterface::RELATIVE_PATH));
    }

    /**
     * @Method("POST")
     * @Route("/", options={"expose"=true})
     *
     * @param Request $request
     * @param TorrentManager $torrentManager
     * @return array
     *
     * @throws \Exception
     */
    public function addTorrents(Request $request, TorrentManager $torrentManager)
    {
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
     *
     * @param TorrentManager $torrentManager
     * @param string $hash
     * @return array
     *
     * @throws \Exception
     */
    public function pauseTorrent(TorrentManager $torrentManager, string $hash)
    {
        $torrentManager->pauseTorrent($hash);
        return [];
    }

    /**
     * @Method("PUT")
     * @Route("/{hash}/resume", options={"expose"=true})
     *
     * @param TorrentManager $torrentManager
     * @param string $hash
     * @return array
     *
     * @throws \Exception
     */
    public function resumeTorrent(TorrentManager $torrentManager, string $hash)
    {
        $torrentManager->resumeTorrent($hash);
        return [];
    }

    /**
     * @Method("DELETE")
     * @Route("/{hash}", options={"expose"=true})
     *
     * @param TorrentManager $torrentManager
     * @param string $hash
     * @return array
     *
     * @throws \Exception
     */
    public function removeTorrent(TorrentManager $torrentManager, string $hash)
    {
        $torrentManager->removeTorrent($hash);
        return [];
    }
}
