<?php

namespace Athorrent\Controller;

use Athorrent\Filesystem\FilesystemAwareTrait;
use Athorrent\Routing\AbstractController;
use Athorrent\View\View;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractFileController extends AbstractController
{
    use FilesystemAwareTrait;

    public function getRouteDescriptors()
    {
        return [
            ['GET', '/', 'listFiles', 'both'],
            ['GET', '/play', 'playFile'],
            ['GET', '/display', 'displayFile'],

            ['GET', '/open', 'openFile'],
            ['GET', '/download', 'downloadFile'],

            ['DELETE', '/', 'removeFile', 'ajax']
        ];
    }

    protected function getBreadcrumb(Application $app, $path)
    {
        $breadcrumb = [$app['translator']->trans('files.root') => ''];

        $parts = explode('/', $path);
        $currentPath = '';

        foreach ($parts as $currentName) {
            $currentPath .= $currentName;
            $breadcrumb[$currentName] = $currentPath;
            $currentPath .= DIRECTORY_SEPARATOR;
        }

        return $breadcrumb;
    }

    public function listFiles(Application $app, Request $request)
    {
        $dirEntry = $this->getEntry($request, $app);

        if ($dirEntry->isRoot() && $this instanceof FileController) {
            $title = $app['translator']->trans('files.title');
        } else {
            $title = $dirEntry->getName();
        }

        $breadcrumb = $this->getBreadcrumb($app, $dirEntry->getPath());
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

    protected function sendFile(Application $app, Request $request, $contentDisposition)
    {
        $entry = $this->getEntry($request, $app, ['path' => true, 'file' => true]);

        $response = $entry->toBinaryFileResponse();

        $response->setPrivate();
        $response->setAutoEtag();
        $response->headers->set('Content-Disposition', $contentDisposition . '; filename="' . $entry->getName() . '"');

        if (!$response->isNotModified($request)) {
            set_time_limit(0);
        }

        return $response;
    }

    public function openFile(Application $app, Request $request)
    {
        return $this->sendFile($app, $request, 'inline');
    }

    public function downloadFile(Application $app, Request $request)
    {
        return $this->sendFile($app, $request, 'attachment');
    }

    public function playFile(Request $request, Application $app)
    {
        $fileEntry = $this->getEntry($request, $app, ['path' => true, 'file' => true]);

        if (!$fileEntry->isPlayable()) {
            throw new \Exception('error.notPlayable');
        }

        $relativePath = $fileEntry->getPath();
        $breadcrumb = self::getBreadcrumb($app, $relativePath);

        if ($fileEntry->isAudio()) {
            $mediaTag = 'audio';
        } elseif ($fileEntry->isVideo()) {
            $mediaTag = 'video';
        }

        return new View([
            'name' => $fileEntry->getName(),
            'breadcrumb' => $breadcrumb,
            'mediaTag' => $mediaTag,
            'type' => $fileEntry->getMimeType(),
            'src' => $relativePath
        ]);
    }

    public function displayFile(Request $request, Application $app)
    {
        $fileEntry = $this->getEntry($request, $app, ['path' => true, 'file' => true]);

        if (!$fileEntry->isDisplayable()) {
            throw new \Exception('error.notDisplayable');
        }

        $relativePath = $fileEntry->getPath();
        $breadcrumb = self::getBreadcrumb($app, $relativePath);

        $data = [
            'name' => $fileEntry->getName(),
            'breadcrumb' => $breadcrumb
        ];

        if ($fileEntry->isText()) {
            $data['text'] = $fileEntry->readFile();
        } elseif ($fileEntry->isImage()) {
            $data['src'] = $relativePath;
        }

        return new View($data);
    }

    public function removeFile(Application $app, Request $request)
    {
        $fileEntry = $this->getEntry($request, $app, ['path' => true]);

        if ($fileEntry->isRoot()) {
            throw new NotFoundHttpException();
        }

        $fileEntry->remove();

        $app['orm.repo.sharing']->deleteByUserAndRoot($fileEntry->getOwner(), $fileEntry->getPath());

        return [];
    }
}
