<?php

namespace Athorrent\Controller;

use Athorrent\Utils\ServiceUnavailableException;
use Athorrent\Utils\TorrentManager;
use Athorrent\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Route("/user/torrents", name="torrents")
 */
class TorrentController extends AbstractController
{
    /**
     * @Route("/", methods="GET", options={"expose"=true})
     *
     * @param TorrentManager $torrentManager
     * @return View
     *
     * @throws \Exception
     */
    public function listTorrents(TorrentManager $torrentManager): View
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
     * @Route("/{hash}/trackers", methods="GET", options={"expose"=true})
     *
     * @param TorrentManager $torrentManager
     * @param string $hash
     * @return View
     *
     * @throws \Exception
     */
    public function listTrackers(TorrentManager $torrentManager, string $hash): View
    {
        $trackers = $torrentManager->listTrackers($hash);

        return new View(['trackers' => $trackers]);
    }

    /**
     * @Route("/files", methods="POST", options={"expose"=true})
     *
     * @param Request $request
     * @param TorrentManager $torrentManager
     * @return array
     *
     * @throws \Exception
     */
    public function uploadTorrent(Request $request, TorrentManager $torrentManager): array
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('upload-torrent-file');

        if ($file && $file->getSize() <= 1048576) {
            if ($file->getMimeType() === 'application/x-bittorrent') {
                $file->move($torrentManager->getTorrentsDirectory(), $file->getClientOriginalName());

                return [];
            }

            throw new BadRequestHttpException('error.NotATorrent');
        }

        throw new BadRequestHttpException('error.fileTooBig');
    }

    /**
     * @Route("/magnet", methods="GET")
     *
     * @param Request $request
     * @param TorrentManager $torrentManager
     * @return RedirectResponse
     *
     * @throws \Exception
     */
    public function addMagnet(Request $request, TorrentManager $torrentManager): RedirectResponse
    {
        $magnet = $request->query->get('magnet');

        if ($magnet) {
            $torrentManager->addTorrentFromMagnet($magnet);
        }

        return new RedirectResponse($this->generateUrl('listTorrents', [], UrlGeneratorInterface::RELATIVE_PATH));
    }

    /**
     * @Route("/", methods="POST", options={"expose"=true})
     *
     * @param Request $request
     * @param TorrentManager $torrentManager
     * @return array
     *
     * @throws \Exception
     */
    public function addTorrents(Request $request, TorrentManager $torrentManager): array
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
     * @Route("/{hash}/pause", methods="PUT", options={"expose"=true})
     *
     * @param TorrentManager $torrentManager
     * @param string $hash
     * @return array
     *
     * @throws \Exception
     */
    public function pauseTorrent(TorrentManager $torrentManager, string $hash): array
    {
        $torrentManager->pauseTorrent($hash);
        return [];
    }

    /**
     * @Route("/{hash}/resume", methods="PUT", options={"expose"=true})
     *
     * @param TorrentManager $torrentManager
     * @param string $hash
     * @return array
     *
     * @throws \Exception
     */
    public function resumeTorrent(TorrentManager $torrentManager, string $hash): array
    {
        $torrentManager->resumeTorrent($hash);
        return [];
    }

    /**
     * @Route("/{hash}", methods="DELETE", options={"expose"=true})
     *
     * @param TorrentManager $torrentManager
     * @param string $hash
     * @return array
     *
     * @throws \Exception
     */
    public function removeTorrent(TorrentManager $torrentManager, string $hash): array
    {
        $torrentManager->removeTorrent($hash);
        return [];
    }
}
