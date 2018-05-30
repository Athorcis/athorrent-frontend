<?php

namespace Athorrent\Controller;

use Athorrent\Database\Repository\SharingRepository;
use Athorrent\Filesystem\UserFilesystemEntry;
use Athorrent\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractFileController extends Controller
{
    protected $translator;

    protected $sharingRepository;

    public function __construct(TranslatorInterface $translator, SharingRepository $sharingRepository)
    {
        $this->translator = $translator;
        $this->sharingRepository = $sharingRepository;
    }

    protected function getBreadcrumb(string $path)
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
     * @Method("GET")
     * @Route("/", options={"expose"=true})
     * @ParamConverter("dirEntry")
     *
     * @param UserFilesystemEntry $dirEntry
     * @return View
     */
    public function listFiles(UserFilesystemEntry $dirEntry)
    {
        if ($dirEntry->isRoot() && $this instanceof FileController) {
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

    protected function sendFile(Request $request, UserFilesystemEntry $entry, $contentDisposition)
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
     * @Method("GET")
     * @Route("/open")
     * @ParamConverter("entry", options={"path": true, "file": true})
     *
     * @param Request $request
     * @param UserFilesystemEntry $entry
     * @return View
     */
    public function openFile(Request $request, UserFilesystemEntry $entry)
    {
        return $this->sendFile($request, $entry, 'inline');
    }

    /**
     * @Method("GET")
     * @Route("/download")
     * @ParamConverter("entry", options={"path": true, "file": true})
     *
     * @param Request $request
     * @param UserFilesystemEntry $entry
     * @return BinaryFileResponse
     */
    public function downloadFile(Request $request, UserFilesystemEntry $entry)
    {
        return $this->sendFile($request, $entry,'attachment');
    }

    /**
     * @Method("GET")
     * @Route("/play")
     * @ParamConverter("entry", options={"path": true, "file": true})
     *
     * @param UserFilesystemEntry $entry
     * @return View
     */
    public function playFile(UserFilesystemEntry $entry)
    {
        if (!$entry->isPlayable()) {
            throw new UnsupportedMediaTypeHttpException('error.notPlayable');
        }

        $relativePath = $entry->getPath();
        $breadcrumb = self::getBreadcrumb($relativePath);

        if ($entry->isAudio()) {
            $mediaTag = 'audio';
        } elseif ($entry->isVideo()) {
            $mediaTag = 'video';
        }

        return new View([
            'name' => $entry->getName(),
            'breadcrumb' => $breadcrumb,
            'mediaTag' => $mediaTag,
            'type' => $entry->getMimeType(),
            'src' => $relativePath
        ]);
    }

    /**
     * @Method("GET")
     * @Route("/display")
     * @ParamConverter("entry", options={"path": true, "file": true})
     *
     * @param UserFilesystemEntry $entry
     * @return View
     */
    public function displayFile(UserFilesystemEntry $entry)
    {
        if (!$entry->isDisplayable()) {
            throw new UnsupportedMediaTypeHttpException('error.notDisplayable');
        }

        $relativePath = $entry->getPath();
        $breadcrumb = self::getBreadcrumb($relativePath);

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
     * @Method("DELETE")
     * @Route("/", options={"expose"=true})
     * @ParamConverter("entry", options={"path": true})
     *
     * @param UserFilesystemEntry $entry
     * @return array
     */
    public function removeFile(UserFilesystemEntry $entry)
    {
        if ($entry->isRoot()) {
            throw new NotFoundHttpException();
        }

        $entry->remove();

        $this->sharingRepository->deleteByUserAndRoot($entry->getOwner(), $entry->getPath());

        return [];
    }
}
