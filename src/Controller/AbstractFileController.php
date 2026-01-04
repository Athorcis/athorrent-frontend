<?php

namespace Athorrent\Controller;

use Athorrent\Database\Repository\SharingRepository;
use Athorrent\Filesystem\AbstractFilesystemEntry;
use Athorrent\Filesystem\Requirements;
use Athorrent\Filesystem\UserFilesystemEntry;
use Athorrent\View\View;
use Athorrent\View\ViewType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractFileController extends AbstractController
{
    public function __construct(protected TranslatorInterface $translator, protected LoggerInterface $logger)
    {
    }

    /**
     * @return string[]
     */
    protected function getBreadcrumb(string $path): array
    {
        $breadcrumb = [$this->translator->trans('files.root') => ''];

        $parts = explode('/', $path);
        $currentPath = '';

        foreach ($parts as $currentName) {
            $currentPath .= $currentName;
            $breadcrumb[$currentName] = $currentPath;
            $currentPath .= DIRECTORY_SEPARATOR;
        }

        return $breadcrumb;
    }

    #[Route(path: '/', methods: 'GET', options: ['expose' => true])]
    public function listFiles(UserFilesystemEntry $dirEntry): View
    {
        if ($this instanceof FileController && $dirEntry->isRoot()) {
            $title = $this->translator->trans('files.title');
        } else {
            $title = $dirEntry->getName();
        }

        $breadcrumb = $this->getBreadcrumb($dirEntry->getPath());
        $entries = $dirEntry->readDirectory(!$dirEntry->isRoot());

        usort($entries, [AbstractFilesystemEntry::class, 'compare']);

        return new View(ViewType::Dynamic, [
            'title' => $title,
            'breadcrumb' => $breadcrumb,
            'files' => $entries,
            '_strings' => [
                'files.sharingLink',
                'files.removalConfirmation',
            ]
        ], 'listFiles');
    }

    protected function sendFile(Request $request, UserFilesystemEntry $entry, $contentDisposition): BinaryFileResponse
    {
        BinaryFileResponse::trustXSendfileTypeHeader();
        $response = $entry->toBinaryFileResponse();

        $response->setPrivate();
        $response->setContentDisposition($contentDisposition, $entry->getName());

        if (!$response->isNotModified($request)) {
            set_time_limit(0);
        }

        return $response;
    }

    protected function getContentDisposition($disposition, $filename): string
    {
        $response = new BinaryFileResponse(__FILE__);
        $response->setContentDisposition($disposition, $filename);

        return $response->headers->get('Content-Disposition');
    }

    protected function sendDirectory(UserFilesystemEntry $entry, $contentDisposition): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'application/x-gzip',
            'Content-Disposition' => $this->getContentDisposition($contentDisposition, $entry->getName() . '.tar.gz'),
            'X-Accel-Buffering' => 'no',
        ];

        return new StreamedResponse(function () use ($entry) {

            $process = Process::fromShellCommandline('tar --create --gzip --file - *', $entry->getRealPath());
            $process->setTimeout(null);

            $process->start(function ($type, $buffer) {
                if (Process::OUT === $type) {
                    echo $buffer;
                    flush();
                }
            });

            $process->wait();

            if (!$process->isSuccessful()) {
                $this->logger->error("directory download failed", [
                    'status' => $process->getExitCode(),
                    'stderr' => $process->getErrorOutput(),
                    'signal' => $process->getStopSignal(),
                ]);
            }
        }, 200, $headers);
    }

    #[Route(path: '/open', methods: 'GET')]
    public function openFile(Request $request, #[Requirements(path: true, file: true)] UserFilesystemEntry $entry): BinaryFileResponse
    {
        return $this->sendFile($request, $entry, 'inline');
    }

    #[Route(path: '/download', methods: 'GET')]
    public function downloadFile(Request $request, #[Requirements(path: true)] UserFilesystemEntry $entry): BinaryFileResponse|StreamedResponse
    {
        if ($entry->isDirectory()) {
            return $this->sendDirectory($entry, 'attachment');
        }

        return $this->sendFile($request, $entry,'attachment');
    }

    #[Route(path: '/play', methods: 'GET')]
    public function playFile(#[Requirements(path: true, file: true)] UserFilesystemEntry $entry): View
    {
        if ($entry->isPlayable()) {
            if ($entry->isAudio()) {
                $mediaTag = 'audio';
            } elseif ($entry->isVideo()) {
                $mediaTag = 'video';
            }
        }

        if (!isset($mediaTag)) {
            throw new UnsupportedMediaTypeHttpException('error.notPlayable');
        }

        $path = $entry->getPath();
        $breadcrumb = $this->getBreadcrumb($path);

        return new View(ViewType::Page, [
            'name' => $entry->getName(),
            'breadcrumb' => $breadcrumb,
            'mediaTag' => $mediaTag,
            'type' => $entry->getMimeType(),
            'src' => $path
        ]);
    }

    #[Route(path: '/display', methods: 'GET')]
    public function displayFile(#[Requirements(path: true, file: true)] UserFilesystemEntry $entry): View
    {
        if (!$entry->isDisplayable()) {
            throw new UnsupportedMediaTypeHttpException('error.notDisplayable');
        }

        $relativePath = $entry->getPath();
        $breadcrumb = $this->getBreadcrumb($relativePath);

        $data = [
            'name' => $entry->getName(),
            'breadcrumb' => $breadcrumb
        ];

        if ($entry->isText()) {
            $data['text'] = $entry->readFile();
        } elseif ($entry->isImage()) {
            $data['src'] = $relativePath;
        }

        return new View(ViewType::Page, $data);
    }

    #[Route(path: '/', methods: 'DELETE', options: ['expose' => true])]
    public function removeFile(#[Requirements(path: true)] UserFilesystemEntry $entry, SharingRepository $sharingRepository): array
    {
        if ($entry->isRoot()) {
            throw $this->createNotFoundException();
        }

        if (!$entry->isWritable()) {
            throw $this->createAccessDeniedException();
        }

        $entry->remove();

        $sharingRepository->deleteByUserAndRoot($entry->getOwner(), $entry->getPath());

        return [];
    }
}
