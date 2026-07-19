<?php

declare(strict_types=1);

namespace Athorrent\Controller;

use Athorrent\Backend\BackendState;
use Athorrent\Backend\BackendUnavailableException;
use Athorrent\UserVisibleException;
use Athorrent\Utils\TorrentAlreadyAdded;
use Athorrent\Utils\TorrentManagerInterface;
use Athorrent\View\View;
use Athorrent\View\ViewType;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route(path: '/user/torrents', name: 'torrents_')]
class TorrentController extends AbstractController
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/', methods: 'GET', options: ['expose' => true])]
    public function listTorrents(TorrentManagerInterface $torrentManager): View
    {
        try {
            $torrents = $torrentManager->getTorrents();

            usort(
                $torrents, fn($a, $b) => strcmp($a['name'], $b['name'])
            );

            $backendAvailable = true;
            $backendStarting = false;
            $backendUpdating = false;
            $backendStopped = false;
            $alertLevel = 'none';

        } catch (BackendUnavailableException $e) {
            $this->logger->error('backend unavailable', ['exception' => $e]);

            $torrents = [];

            $backendAvailable = false;
            $backendStarting = $e->state === BackendState::Starting;
            $backendUpdating = $e->state === BackendState::Updating;
            $backendStopped = $e->state === BackendState::Stopped;
            $alertLevel = $backendStarting || $backendUpdating ? 'warning' : 'error';
        }

        return new View(ViewType::Dynamic, [
            'torrents' => $torrents,
            'backend_available' => $backendAvailable,
            'backend_starting' => $backendStarting,
            'backend_updating' => $backendUpdating,
            'backend_stopped' => $backendStopped,
            'alert_level' => $alertLevel,
            '_strings' => [
                'torrents.addTorrent',
                'torrents.magnetModal.title',
                'torrents.magnetModal.subtitle',
                'torrents.add',
                'common.cancel',
                'error.unknownError',
                'error.notATorrent',
                'error.fileTooBig',
                'error.serverError',
                'error.title',
            ]
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/files', methods: 'POST', options: ['expose' => true])]
    public function uploadTorrent(
        Request $request,
        TorrentManagerInterface $torrentManager,
        ValidatorInterface $validator,
        #[MapQueryParameter] ?int $downloadLimit = null,
    ): array
    {
        $this->applyTestDownloadLimit($downloadLimit, $torrentManager);

        /** @var UploadedFile $file */
        $file = $request->files->get('file');

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

        $path = $file->getRealPath();

        try {
            $result = $torrentManager->addTorrentFromFile($path);
        }
        catch (TorrentAlreadyAdded) {
            // NOOP
        }
        catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 415) {
                throw new UserVisibleException("error.invalidTorrentFile");
            }

            throw $e;
        }

        return ['hash' => $result['hash'] ?? null];
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/magnet', methods: 'GET')]
    public function addMagnet(
        #[MapQueryParameter] ?string $magnet = null,
    ): RedirectResponse
    {
        $parameters = $magnet !== null ? ['magnet' => $magnet] : [];

        return new RedirectResponse($this->generateUrl('listTorrents', $parameters, UrlGeneratorInterface::RELATIVE_PATH));
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/magnets', methods: 'POST', options: ['expose' => true])]
    public function addMagnets(
        Request $request,
        TorrentManagerInterface $torrentManager,
        #[MapQueryParameter] ?int $downloadLimit = null,
    ): array
    {
        $this->applyTestDownloadLimit($downloadLimit, $torrentManager);

        $magnets = $request->request->all('magnets');

        $torrentIds = [];
        $usedMagnets = [];

        foreach ($magnets as $magnet) {
            if (in_array($magnet, $usedMagnets, true)) {
                continue;
            }

            try {
                $result = $torrentManager->addTorrentFromMagnet($magnet);
            }
            catch (TorrentAlreadyAdded) {
                // NOOP
            }

            $usedMagnets[] = $magnet;

            if (isset($result['hash'])) {
                $torrentIds[] = $result['hash'];
            }
        }

        return [
            'torrentIds' => $torrentIds,
        ];
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/{hash}/pause', methods: 'PUT', options: ['expose' => true])]
    public function pauseTorrent(TorrentManagerInterface $torrentManager, string $hash): array
    {
        $torrentManager->pauseTorrent($hash);
        return [];
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/{hash}/resume', methods: 'PUT', options: ['expose' => true])]
    public function resumeTorrent(TorrentManagerInterface $torrentManager, string $hash): array
    {
        $torrentManager->resumeTorrent($hash);
        return [];
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/{hash}', methods: 'DELETE', options: ['expose' => true])]
    public function removeTorrent(TorrentManagerInterface $torrentManager, string $hash): array
    {
        $torrentManager->removeTorrent($hash);
        return [];
    }

    private function applyTestDownloadLimit(?int $downloadLimit, TorrentManagerInterface $torrentManager): void
    {
        if ($downloadLimit === null) {
            return;
        }

        if (($_ENV['APP_ENV'] ?? '') === 'test') {
            $torrentManager->setDownloadLimit($downloadLimit);
        }
    }
}
