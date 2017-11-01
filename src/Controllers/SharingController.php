<?php

namespace Athorrent\Controllers;

use Athorrent\Entity\Sharing;
use Athorrent\Routing\AbstractController;
use Athorrent\Utils\FileManager;
use Athorrent\View\View;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class SharingController extends AbstractController
{
    protected function getRouteDescriptors()
    {
        return [
            ['GET', '/', 'listSharings'],

            ['POST', '/', 'addSharing', 'ajax'],
            ['POST', '/{token}', 'removeSharing', 'ajax']
        ];
    }

    public function listSharings(Application $app, Request $request)
    {
        if ($request->query->has('page')) {
            $page = $request->query->get('page');

            if (!is_numeric($page) || $page < 1) {
                $app->abort(400);
            }
        } else {
            $page = 1;
        }

        $offset = 10 * ($page - 1);

        $sharings = Sharing::loadByUserId($app['user']->getUserId(), $offset, 10, $total);

        if ($offset >= $total && $total > 0) {
            $app->abort(404);
        }

        $lastPage = ceil($total / 10);

        return new View([
            'sharings' => $sharings,
            'page' => $page,
            'lastPage' => $lastPage
        ]);
    }

    public function addSharing(Application $app, Request $request)
    {
        if (!$request->request->has('path')) {
            $app->abort(400);
        }

        $fileManager = FileManager::getByUser($app['user']->getUserId());
        $path = $fileManager->getAbsolutePath($request->request->get('path'));

        if (!file_exists($path)) {
            $app->abort(404);
        }

        $sharing = new Sharing(null, $app['user']->getUserId(), $fileManager->getRelativePath($path));
        $sharing->save();

        return [$app->url('listFiles', ['token' => $sharing->getToken(), '_prefixId' => 'sharings'])];
    }

    public function removeSharing(Application $app, Request $request, $token)
    {
        if (!Sharing::deleteByToken($token, $app['user']->getUserId())) {
            $app->abort(404, 'error.sharingNotFound');
        }

        return [];
    }
}
