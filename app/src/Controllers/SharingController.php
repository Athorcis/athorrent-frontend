<?php

namespace Athorrent\Controllers;

use Athorrent\Entity\Sharing;
use Athorrent\Utils\FileManager;
use Symfony\Component\HttpFoundation\Request;

class SharingController extends AbstractController {
    protected static $actionPrefix = 'sharings_';

    protected static $routePattern = '/sharings';

    protected static function buildRoutes() {
        $routes = parent::buildRoutes();

        $routes[] = array('GET', '/', 'listSharings');

        return $routes;
    }

    protected static function buildAjaxRoutes() {
        $routes = parent::buildAjaxRoutes();

        $routes[] = array('POST', '/', 'addSharing');
        $routes[] = array('DELETE', '/{token}', 'removeSharing');

        return $routes;
    }

    protected function listSharings(Request $request) {
        if ($request->query->has('page')) {
            $page = $request->query->get('page');

            if (!is_numeric($page) || $page < 1) {
                $app->abort(400);
            }
        } else {
            $page = 1;
        }

        $offset = 10 * ($page - 1);

        $sharings = Sharing::loadByUserId($this->getUserId(), $offset, 10, $total);

        if ($offset >= $total && $total > 0) {
            $this->abort(404);
        }

        $lastPage = ceil($total / 10);

        return $this->render(array (
            'sharings' => $sharings,
            'page' => $page,
            'lastPage' => $lastPage
        ));
    }

    protected function addSharing(Request $request) {
        if (!$request->request->has('path')) {
            return $this->abort(400);
        }

        $fileManager = FileManager::getByUser($this->getUserId());
        $path = $fileManager->getAbsolutePath($request->request->get('path'));

        if (!file_exists($path)) {
            return $this->abort(404);
        }

        $sharing = new Sharing(null, $this->getUserId(), $fileManager->getRelativePath($path));
        $sharing->save();

        return $this->success($this->url('listFiles', array('token' => $sharing->getToken()), 'sharings_'));
    }

    protected function removeSharing(Request $request, $token) {
        if (!Sharing::deleteByToken($token, $this->getUserId())) {
            return $this->abort(404, 'error.sharingNotFound');
        }

        return $this->success();
    }
}

?>
