<?php

namespace Athorrent\Controllers;

use Athorrent\Entity\Sharing;
use Athorrent\Utils\FileManager;
use Athorrent\Utils\FileUtils;
use Athorrent\Utils\MimeType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;

class AbstractFileController extends AbstractController
{
    protected static function buildRoutes()
    {
        $routes = parent::buildRoutes();

        $routes[] = ['GET', '/', 'listFiles'];
        $routes[] = ['GET', '/play', 'playFile'];
        $routes[] = ['GET', '/display', 'displayFile'];

        $routes[] = ['GET', '/open', 'openFile'];
        $routes[] = ['GET', '/download', 'downloadFile'];

        return $routes;
    }

    protected static function buildAjaxRoutes()
    {
        $routes = parent::buildAjaxRoutes();

        $routes[] = ['GET', '/', 'listFiles'];

        $routes[] = ['POST', '/remove', 'removeFile'];

        $routes[] = ['GET', '/direct', 'getDirectLink'];

        return $routes;
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

    protected function getJsVariables()
    {
        global $app;

        $jsVariables = parent::getJsVariables();
        $jsVariables['locale']['files.directLink'] = $app['translator']->trans('files.directLink');
        $jsVariables['locale']['files.sharingLink'] = $app['translator']->trans('files.sharingLink');

        return $jsVariables;
    }

    protected function abort($code, $error = null)
    {
        if ($error === null && $code === 404) {
            $error = 'error.fileNotFound';
        }

        parent::abort($code, $error);
    }

    protected function listFiles(Request $request, FileManager $fileManager)
    {
        global $app;

        $path = $fileManager->getAbsolutePath($request->query->get('path'));

        if (!$path) {
            return $this->abort(404);
        }

        $breadcrumb = self::getBreadcrumb($fileManager, $path);
        $result = $fileManager->listEntries($path);

        $title = $result['name'];

        if ($fileManager->isRoot($path) && $this instanceof FileController) {
            $title = $app['translator']->trans('files.title');
        }

        return $this->render([
            'title' => $title,
            'dir_size' => $result['size'],
            'breadcrumb' => $breadcrumb,
            'files' => $result['files']
        ], 'listFiles');
    }

    protected function openFile(Request $request, FileManager $fileManager)
    {
        if (!$request->query->has('path')) {
            return $this->abort(400);
        }

        $path = $fileManager->getAbsolutePath($request->query->get('path'));

        if (!is_file($path)) {
            return $this->abort(404);
        }

        $response = new BinaryFileResponse($path, 200, [
            "Content-Disposition" => ' inline; filename="' . pathinfo($path, PATHINFO_BASENAME) . '"'
        ], null, true);
        
        if (!$response->isNotModified($request)) {
            set_time_limit(0);
        }
        
        return $response;
    }

    protected function downloadFile(Request $request, FileManager $fileManager)
    {
        if (!$request->query->has('path')) {
            return $this->abort(400);
        }

        $path = $fileManager->getAbsolutePath($request->query->get('path'));

        if (!is_file($path)) {
            return $this->abort(404);
        }

        set_time_limit(0);

        return $this->sendFile($path, 200, [
            'Content-Disposition' => ' attachment; filename="' . pathinfo($path, PATHINFO_BASENAME) . '"'
        ]);
    }

    protected function playFile(Request $request, FileManager $fileManager)
    {
        if (!$request->query->has('path')) {
            return $this->abort(400);
        }

        $path = $fileManager->getAbsolutePath($request->query->get('path'));

        if (!is_file($path)) {
            return $this->abort(404);
        }

        $mimeType = FileUtils::getMimeType($path);
        
        if (!MimeType::isPlayable($mimeType)) {
            return $this->abort(500, 'error.notPlayable');
        }

        $relativePath = $fileManager->getRelativePath($path);
        $breadcrumb = self::getBreadcrumb($fileManager, $path, false);

        $name = pathinfo($relativePath, PATHINFO_BASENAME);

        if (MimeType::isAudio($mimeType)) {
            $mediaTag = "audio";
        } elseif (MimeType::isVideo($mimeType)) {
            $mediaTag = "video";
        }
        
        return $this->render([
            'name' => $name,
            'breadcrumb' => $breadcrumb,
            'mediaTag' => $mediaTag,
            'type' => explode(";", $mimeType)[0],
            'src' => $relativePath
        ]);
    }

    protected function displayFile(Request $request, FileManager $fileManager)
    {
        if (!$request->query->has('path')) {
            return $this->abort(400);
        }

        $path = $fileManager->getAbsolutePath($request->query->get('path'));

        if (!is_file($path)) {
            return $this->abort(404);
        }

        $mimeType = FileUtils::getMimeType($path);
        
        if (!MimeType::isDisplayable($mimeType)) {
            return $this->abort(500, 'error.notDisplayable');
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
        
        return $this->render($data);
    }

    protected function removeFile(Request $request, FileManager $fileManager)
    {
        if (!$request->request->has('path')) {
            return $this->abort(400);
        }

        $path = $fileManager->getAbsolutePath($request->request->get('path'));

        if (!$path) {
            return $this->abort(404);
        }

        if ($fileManager->remove($path)) {
            return $this->success();
        }

        return $this->abort(500, 'error.cannotRemoveFile');
    }

    protected function getDirectLink(Request $request, FileManager $fileManager)
    {
        if (!$request->query->has('path')) {
            return $this->abort(400);
        }

        $path = $fileManager->getAbsolutePath($request->query->get('path'));

        if (!is_file($path)) {
            return $this->abort(404);
        }

        if (!$fileManager->isWritable()) {
            return $this->abort(500);
        }

        $relativePath = $fileManager->getRelativePath($path);
        list($parentPath) = explode('/', $relativePath);

        $sharings = Sharing::loadByPathRecursively($parentPath, $fileManager->getOwnerId());

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

        $url = $this->url('openFile', [
            'token' => $sharing->getToken(),
            'path' => $finalPath
        ], 'sharings_');
        
        return $this->success($url);
    }
}
