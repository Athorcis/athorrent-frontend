<?php

namespace Athorrent\Controller;

use Athorrent\Utils\ServiceUnavailableException;
use Athorrent\Utils\TorrentManager;
use Athorrent\View\View;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(path: '/user/torrents', name: 'torrents')]
class TorrentController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route(path: '/', methods: 'GET', options: ['expose' => true])]
    public function listTorrents(TorrentManager $torrentManager): View
    {
        try {
            $torrents = $torrentManager->getTorrents();
            $clientUpdating = false;

            usort(
                $torrents, fn($a, $b) => strcmp($a['name'], $b['name'])
            );
        } catch (ServiceUnavailableException) {
            $torrents = [];
            $clientUpdating = true;
        }

        return new View([
            'torrents' => $torrents,
            'client_updating' => $clientUpdating,
            '_strings' => ['torrents.dropzone', 'error.notATorrent', 'error.fileTooBig', 'error.serverError']
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/{hash}/trackers', methods: 'GET', options: ['expose' => true])]
    public function listTrackers(TorrentManager $torrentManager, string $hash): View
    {
        $trackers = $torrentManager->listTrackers($hash);

        return new View(['trackers' => $trackers]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/files', methods: 'POST', options: ['expose' => true])]
    public function uploadTorrent(Request $request, TorrentManager $torrentManager, LoggerInterface $logger): array
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('upload-torrent-file');

        if ($file === null) {
            throw new BadRequestException('error.fileRequired');
        }

        if (!$file->isValid()) {
            $error = $file->getError();

            if ($error === UPLOAD_ERR_INI_SIZE) {
                throw new BadRequestException('error.fileTooBig');
            }

            $logger->error('upload failed with an unexpected error', [
                'error' => $error,
                'file' => $file
            ]);

            throw new BadRequestException("error.unknownError");
        }

        if ($file->getSize() > 1_048_576) {
            throw new BadRequestException('error.fileTooBig');
        }

        if ($file->getMimeType() !== 'application/x-bittorrent') {
            throw new BadRequestException('error.notATorrent');
        }

        $torrentsDir = $torrentManager->ensureTorrentsDirExists();
        $file->move($torrentsDir, $file->getClientOriginalName());

        return [];
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/magnet', methods: 'GET')]
    public function addMagnet(
        TorrentManager $torrentManager,
        #[MapQueryParameter] ?string $magnet = null,
        ): RedirectResponse
    {
        if ($magnet) {
            $torrentManager->addTorrentFromMagnet($magnet);
        }

        return new RedirectResponse($this->generateUrl('listTorrents', [], UrlGeneratorInterface::RELATIVE_PATH));
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/', methods: 'POST', options: ['expose' => true])]
    public function addTorrents(Request $request, TorrentManager $torrentManager): array
    {
        $files = $request->request->all('add-torrent-files');
        $magnets = $request->request->all('add-torrent-magnets');

        $torrentsDir = $torrentManager->getTorrentsDirectory();

        if ($files) {
            foreach ($files as $file) {
                $torrentPath = Path::join($torrentsDir, $file);

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
     * @throws Exception
     */
    #[Route(path: '/{hash}/pause', methods: 'PUT', options: ['expose' => true])]
    public function pauseTorrent(TorrentManager $torrentManager, string $hash): array
    {
        $torrentManager->pauseTorrent($hash);
        return [];
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/{hash}/resume', methods: 'PUT', options: ['expose' => true])]
    public function resumeTorrent(TorrentManager $torrentManager, string $hash): array
    {
        $torrentManager->resumeTorrent($hash);
        return [];
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/{hash}', methods: 'DELETE', options: ['expose' => true])]
    public function removeTorrent(TorrentManager $torrentManager, string $hash): array
    {
        $torrentManager->removeTorrent($hash);
        return [];
    }
}
