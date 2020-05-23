<?php

namespace Athorrent\Controller;

use Athorrent\Database\Repository\SharingRepository;
use Athorrent\Filesystem\UserFilesystemEntry;
use Athorrent\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractFileController extends AbstractController
{
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

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

    /**
     * @Route("/", methods="GET", options={"expose"=true})
     * @ParamConverter("dirEntry")
     *
     * @param UserFilesystemEntry $dirEntry
     * @return View
     */
    public function listFiles(UserFilesystemEntry $dirEntry): View
    {
        if ($this instanceof FileController && $dirEntry->isRoot()) {
            $title = $this->translator->trans('files.title');
        } else {
            $title = $dirEntry->getName();
        }

        $breadcrumb = $this->getBreadcrumb($dirEntry->getPath());
        $entries = $dirEntry->readDirectory(!$dirEntry->isRoot());

        return new View([
            'title' => $title,
            'breadcrumb' => $breadcrumb,
            'files' => $entries,
            '_strings' => [
                'files.sharingLink'
            ]
        ], 'listFiles');
    }

    protected function sendFile(Request $request, UserFilesystemEntry $entry, $contentDisposition): BinaryFileResponse
    {
        $response = $entry->toBinaryFileResponse();

        $response->setPrivate();
        $response->setAutoEtag();
        $response->headers->set('Content-Disposition', $contentDisposition . '; filename="' . $entry->getName() . '"');

        if (!$response->isNotModified($request)) {
            set_time_limit(0);
        }

        return $response;
    }

    /**
     * @Route("/open", methods="GET")
     * @ParamConverter("entry", options={"path": true, "file": true})
     *
     * @param Request $request
     * @param UserFilesystemEntry $entry
     * @return BinaryFileResponse
     */
    public function openFile(Request $request, UserFilesystemEntry $entry): BinaryFileResponse
    {
        return $this->sendFile($request, $entry, 'inline');
    }

    /**
     * @Route("/download", methods="GET")
     * @ParamConverter("entry", options={"path": true, "file": true})
     *
     * @param Request $request
     * @param UserFilesystemEntry $entry
     * @return BinaryFileResponse
     */
    public function downloadFile(Request $request, UserFilesystemEntry $entry): BinaryFileResponse
    {
        return $this->sendFile($request, $entry,'attachment');
    }

    /**
     * @Route("/play", methods="GET")
     * @ParamConverter("entry", options={"path": true, "file": true})
     *
     * @param UserFilesystemEntry $entry
     * @return View
     */
    public function playFile(UserFilesystemEntry $entry): View
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

        return new View([
            'name' => $entry->getName(),
            'breadcrumb' => $breadcrumb,
            'mediaTag' => $mediaTag,
            'type' => $entry->getMimeType(),
            'src' => $path
        ]);
    }

    /**
     * @Route("/display", methods="GET")
     * @ParamConverter("entry", options={"path": true, "file": true})
     *
     * @param UserFilesystemEntry $entry
     * @return View
     */
    public function displayFile(UserFilesystemEntry $entry): View
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

        return new View($data);
    }

    /**
     * @Route("/", methods="DELETE", options={"expose"=true})
     * @ParamConverter("entry", options={"path": true})
     *
     * @param UserFilesystemEntry $entry
     * @param SharingRepository $sharingRepository
     * @return array
     */
    public function removeFile(UserFilesystemEntry $entry, SharingRepository $sharingRepository): array
    {
        if ($entry->isRoot()) {
            throw new NotFoundHttpException();
        }

        $entry->remove();

        $sharingRepository->deleteByUserAndRoot($entry->getOwner(), $entry->getPath());

        return [];
    }
}
