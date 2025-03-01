<?php

namespace Athorrent\Controller;

use Athorrent\Backend\BackendState;
use Athorrent\Backend\BackendUnavailableException;
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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route(path: '/user/torrents', name: 'torrents')]
class TorrentController extends AbstractController
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/', methods: 'GET', options: ['expose' => true])]
    public function listTorrents(TorrentManager $torrentManager): View
    {
        $backendAvailable = true;

        try {
            $torrents = $torrentManager->getTorrents();

            usort(
                $torrents, fn($a, $b) => strcmp($a['name'], $b['name'])
            );
        } catch (BackendUnavailableException $e) {
            $this->logger->error('backend unavailable', ['exception' => $e]);
            $backendAvailable = false;
            $torrents = [];
        }

        if ($backendAvailable) {
            $backendStarting = false;
            $backendUpdating = false;
            $backendStopped = false;

            $alertLevel = 'none';
        }
        else {
            $backendState = $e->state;
            $backendStarting = $backendState === BackendState::Starting;
            $backendUpdating = $backendState === BackendState::Updating;
            $backendStopped = $backendState === BackendState::Stopped;

            $alertLevel = $backendStarting || $backendUpdating ? 'warning' : 'error';
        }

        return new View([
            'torrents' => $torrents,
            'backend_available' => $backendAvailable,
            'backend_starting' => $backendStarting,
            'backend_updating' => $backendUpdating,
            'backend_stopped' => $backendStopped,
            'alert_level' => $alertLevel,
            '_strings' => ['torrents.dropzone', 'error.unknownError', 'error.notATorrent', 'error.fileTooBig', 'error.serverError']
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/{hash}/trackers', methods: 'GET', options: ['expose' => true])]
    public function listTrackers(TorrentManager $torrentManager, string $hash): View
    {
        $trackers = $torrentManager->listTrackers($hash);

        foreach ($trackers as &$tracker) {
            if ($tracker['peers'] === -1) {
                $tracker['peers'] = '-';
            }

            if ($tracker['seeds'] === -1) {
                $tracker['seeds'] = '-';
            }
        }

        return new View(['trackers' => $trackers]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/files', methods: 'POST', options: ['expose' => true])]
    public function uploadTorrent(
        Request $request,
        TorrentManager $torrentManager,
        ValidatorInterface $validator,
    ): array
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('upload-torrent-file');

        $violations = $validator->validate($file, [
            new Assert\NotBlank(message: 'error.fileRequired'),
            new Assert\File(
                maxSize: 1_048_576,
                mimeTypes: ['application/x-bittorrent'],

                maxSizeMessage: 'error.fileTooBig',
                mimeTypesMessage: 'error.notATorrent',
                uploadIniSizeErrorMessage: 'error.fileTooBig',
            ),
        ]);

        if (count($violations) > 0) {
            throw new BadRequestException($violations[0]->getMessage());
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

        foreach ($files as $file) {
            $torrentPath = Path::join($torrentsDir, $file);

            if (file_exists($torrentPath)) {
                $torrentManager->addTorrentFromFile($torrentPath);
            }
        }

        foreach ($magnets as $magnet) {
            $torrentManager->addTorrentFromMagnet($magnet);
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
