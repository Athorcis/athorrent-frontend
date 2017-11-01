<?php

namespace Athorrent\Controllers;

use Athorrent\Database\Entity\Sharing;
use Athorrent\Routing\AbstractController;
use Athorrent\Utils\FileManager;
use Athorrent\Utils\FileUtils;
use Athorrent\Utils\MimeType;
use Athorrent\View\View;
use Silex\Application;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;

class AbstractFileController extends AbstractController
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

    private static function getBreadcrumb($fileManager, $path, $trim = true)
    {
        global $app;

        $breadcrumb = [$app['translator']->trans('files.root') => ''];

        $parts = explode('/', $fileManager->getRelativePath($path));
        $currentPath = '';

        if (is_file($path) && $trim) {
            array_pop($parts);
        }

        foreach ($parts as $currentName) {
            $currentPath .= $currentName;
            $breadcrumb[$currentName] = $currentPath;
            $currentPath .= DIRECTORY_SEPARATOR;
        }

        return $breadcrumb;
    }

    public function listFiles(Application $app, Request $request)
    {
        $fileManager = $this->getFileManager($app);

        $path = $fileManager->getAbsolutePath($request->query->get('path'));

        if (!$path) {
            $app->abort(404);
        }

        $breadcrumb = self::getBreadcrumb($fileManager, $path);
        $result = $fileManager->listEntries($path);

        $title = $result['name'];

        if ($fileManager->isRoot($path) && $this instanceof FileController) {
            $title = $app['translator']->trans('files.title');
        }

        return new View([
            'title' => $title,
            'dir_size' => $result['size'],
            'breadcrumb' => $breadcrumb,
            'files' => $result['files'],
            '_strings' => [
                'files.directLink',
                'files.sharingLink'
            ]
        ], 'listFiles');
    }

    public function openFile(Application $app, Request $request)
    {
        $fileManager = $this->getFileManager($app);

        if (!$request->query->has('path')) {
            $app->abort(400);
        }

        $path = $fileManager->getAbsolutePath($request->query->get('path'));

        if (!is_file($path)) {
            $app->abort(404);
        }

        $response = new BinaryFileResponse($path, 200, [
            "Content-Disposition" => ' inline; filename="' . pathinfo($path, PATHINFO_BASENAME) . '"'
        ], null, true);
        
        if (!$response->isNotModified($request)) {
            set_time_limit(0);
        }
        
        return $response;
    }

    public function downloadFile(Application $app, Request $request)
    {
        $fileManager = $this->getFileManager($app);

        if (!$request->query->has('path')) {
            $app->abort(400);
        }

        $path = $fileManager->getAbsolutePath($request->query->get('path'));

        if (!is_file($path)) {
            $app->abort(404);
        }

        set_time_limit(0);

        return $app->sendFile($path, 200, [
            'Content-Disposition' => ' attachment; filename="' . pathinfo($path, PATHINFO_BASENAME) . '"'
        ]);
    }

    public function playFile(Application $app, Request $request)
    {
        $fileManager = $this->getFileManager($app);

        if (!$request->query->has('path')) {
            $app->abort(400);
        }

        $path = $fileManager->getAbsolutePath($request->query->get('path'));

        if (!is_file($path)) {
            $app->abort(404);
        }

        $mimeType = FileUtils::getMimeType($path);
        
        if (!MimeType::isPlayable($mimeType)) {
            $app->abort(500, 'error.notPlayable');
        }

        $relativePath = $fileManager->getRelativePath($path);
        $breadcrumb = self::getBreadcrumb($fileManager, $path, false);

        $name = pathinfo($relativePath, PATHINFO_BASENAME);

        if (MimeType::isAudio($mimeType)) {
            $mediaTag = "audio";
        } elseif (MimeType::isVideo($mimeType)) {
            $mediaTag = "video";
        }
        
        return new View([
            'name' => $name,
            'breadcrumb' => $breadcrumb,
            'mediaTag' => $mediaTag,
            'type' => explode(";", $mimeType)[0],
            'src' => $relativePath
        ]);
    }

    public function displayFile(Application $app, Request $request)
    {
        $fileManager = $this->getFileManager($app);

        if (!$request->query->has('path')) {
            $app->abort(400);
        }

        $path = $fileManager->getAbsolutePath($request->query->get('path'));

        if (!is_file($path)) {
            $app->abort(404);
        }

        $mimeType = FileUtils::getMimeType($path);
        
        if (!MimeType::isDisplayable($mimeType)) {
            $app->abort(500, 'error.notDisplayable');
        }

        $relativePath = $fileManager->getRelativePath($path);
        $breadcrumb = self::getBreadcrumb($fileManager, $path, false);

        $name = pathinfo($relativePath, PATHINFO_BASENAME);
        
        $data = [
            "name" => $name,
            "breadcrumb" => $breadcrumb
        ];
        
        if (MimeType::isText($mimeType)) {
            $data["text"] = file_get_contents($path);
        } elseif (MimeType::isImage($mimeType)) {
            $data["src"] = $relativePath;
        }
        
        return new View($data);
    }

    public function removeFile(Application $app, Request $request)
    {
        $fileManager = $this->getFileManager($app);

        if (!$request->request->has('path')) {
            $app->abort(400);
        }

        $path = $fileManager->getAbsolutePath($request->request->get('path'));

        if (!$path) {
            $app->abort(404);
        }

        if ($fileManager->remove($path)) {
            $app['orm.repo.sharing']->deleteByUserAndRoot($app['orm.em']->getReference('Athorrent\Database\Entity\User', $fileManager->getOwnerId()), $path);

            return [];
        }

        $app->abort(500, 'error.cannotRemoveFile');
    }

    public function getDirectLink(Application $app, Request $request)
    {
        $fileManager = $this->getFileManager($app);

        if (!$request->query->has('path')) {
            $app->abort(400);
        }

        $path = $fileManager->getAbsolutePath($request->query->get('path'));

        if (!is_file($path)) {
            $app->abort(404);
        }

        if (!$fileManager->isWritable()) {
            $app->abort(500);
        }

        $relativePath = $fileManager->getRelativePath($path);
        list($parentPath) = explode('/', $relativePath);

        $sharings = $app['orm.repo.sharing']->findByUserAndRoot($app['orm.em']->getReference('Athorrent\Database\Entity\User', $fileManager->getOwnerId()), $parentPath);

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
