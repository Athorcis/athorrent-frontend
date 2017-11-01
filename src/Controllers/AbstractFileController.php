<?php

namespace Athorrent\Controllers;

use Athorrent\Database\Entity\Sharing;
use Athorrent\Filesystem\MimeType;
use Athorrent\Filesystem\UserFilesystem;
use Athorrent\Routing\AbstractController;
use Athorrent\View\View;
use Silex\Application;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractFileController extends AbstractController
{
    protected function getRouteDescriptors()
    {
        return [
            ['GET', '/', 'listFiles', 'both'],
            ['GET', '/play', 'playFile'],
            ['GET', '/display', 'displayFile'],

            ['GET', '/open', 'openFile'],
            ['GET', '/download', 'downloadFile'],

            ['POST', '/remove', 'removeFile', 'ajax'],

            ['GET', '/direct', 'getDirectLink', 'ajax']
        ];
    }

    abstract protected function getFilesystem(Application $app);

    protected function getAbsoluteFilePath(Application $app, UserFilesystem $fs)
    {
        $rawPath = $app['request_stack']->getCurrentRequest()->get('path');

        if ($rawPath === null) {
            $app->abort(400);
        }

        $absolutePath = $fs->getAbsolutePath($rawPath);

        if (!is_file($absolutePath)) {
            $app->abort(404);
        }

        return $absolutePath;
    }

    protected function getRelativeFilePath(Application $app, UserFilesystem $fs)
    {
        return $fs->getRelativePath($this->getAbsoluteFilePath($app, $fs));
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
        $fs = $this->getFilesystem($app);
        $path = $request->query->get('path');

        if ($fs->isRoot($path) && $this instanceof FileController) {
            $title = $app['translator']->trans('files.title');
        } else {
            $title = basename($fs->getAbsolutePath($path));
        }

        $breadcrumb = $this->getBreadcrumb($app, $fs->getRelativePath($path));
        $entries = $fs->list($path);

        return new View([
            'title' => $title,
            'breadcrumb' => $breadcrumb,
            'files' => $entries,
            '_strings' => [
                'files.directLink',
                'files.sharingLink'
            ]
        ], 'listFiles');
    }

    protected function sendFile(Application $app, Request $request, $contentDisposition)
    {
        $path = $this->getAbsoluteFilePath($app, $this->getFilesystem($app));

        $response = new BinaryFileResponse($path, 200, [
            'Content-Disposition' => ' ' . $contentDisposition . '; filename="' . basename($path) . '"'
        ], false, null, true);
        
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

    public function playFile(Application $app)
    {
        $fs = $this->getFilesystem($app);
        $path = $this->getRelativeFilePath($app, $fs);

        $mimeType = $fs->getMimeType($path);
        
        if (!MimeType::isPlayable($mimeType)) {
            $app->abort(500, 'error.notPlayable');
        }

        $breadcrumb = self::getBreadcrumb($app, $path, false);

        $name = basename($path);

        if (MimeType::isAudio($mimeType)) {
            $mediaTag = 'audio';
        } elseif (MimeType::isVideo($mimeType)) {
            $mediaTag = 'video';
        }
        
        return new View([
            'name' => $name,
            'breadcrumb' => $breadcrumb,
            'mediaTag' => $mediaTag,
            'type' => explode(';', $mimeType)[0],
            'src' => $path
        ]);
    }

    public function displayFile(Application $app)
    {
        $fs = $this->getFilesystem($app);
        $path = $this->getAbsoluteFilePath($app, $fs);
        $relativePath = $fs->getRelativePath($path);

        $mimeType = $fs->getMimeType($relativePath);
        
        if (!MimeType::isDisplayable($mimeType)) {
            return $app->abort(500, 'error.notDisplayable');
        }

        $relativePath = $fs->getRelativePath($path);
        $breadcrumb = self::getBreadcrumb($app, $fs->getRelativePath($path), false);

        $name = pathinfo($relativePath, PATHINFO_BASENAME);
        
        $data = [
            'name' => $name,
            'breadcrumb' => $breadcrumb
        ];
        
        if (MimeType::isText($mimeType)) {
            $data['text'] = file_get_contents($path);
        } elseif (MimeType::isImage($mimeType)) {
            $data['src'] = $relativePath;
        }
        
        return new View($data);
    }

    public function removeFile(Application $app, Request $request)
    {
        $path = $request->get('path');

        if ($path === null) {
            $app->abort(400);
        }

        $fs = $this->getFilesystem($app);

        if ($fs->isRoot($path)) {
            $app->abort(404);
        }

        $fs->remove($path);
        $app['orm.repo.sharing']->deleteByUserAndRoot($fs->getUser(), $path);

        return [];
    }

    public function getDirectLink(Application $app)
    {
        $fs = $this->getFilesystem($app);
        $path = $this->getRelativeFilePath($app, $fs);

        if (!$fs->isWritable()) {
            return $app->abort(500);
        }

        list($parentPath) = explode('/', $path);

        $sharings = $app['orm.repo.sharing']->findByUserAndRoot($fs->getUser(), $parentPath);

        if (count($sharings) > 0) {
            $sharing = $sharings[0];
        } else {
            $sharing = new Sharing(null, $fileManager->getOwnerId(), $relativePath);
            $sharing->save();
        }

        $sharingPath = $sharing->getPath();

        if (is_file($fileManager->getAbsolutePath($sharingPath))) {
            $sharingPath = dirname($sharingPath);

            if ($sharingPath === '.') {
                $sharingPath = '';
            }
        }

        $finalPath = str_replace($sharingPath, '', $relativePath);

        return $app->url('openFile', [
            '_prefixId' => 'sharings',
            'token' => $sharing->getToken(),
            'path' => $finalPath
        ]);
    }
}
