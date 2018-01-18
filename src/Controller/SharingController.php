<?php

namespace Athorrent\Controller;

use Athorrent\Database\Entity\Sharing;
use Athorrent\Routing\AbstractController;
use Athorrent\View\PaginatedView;
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
        return new PaginatedView($request, $app['orm.repo.sharing'], 10, ['user', $app['user']]);
    }

    public function addSharing(Application $app, Request $request)
    {
        if (!$request->request->has('path')) {
            $app->abort(400);
        }

        $fileManager = $app['user.fs'];
        $path = $fileManager->getAbsolutePath($request->request->get('path'));

        if (!file_exists($path)) {
            $app->abort(404);
        }

        $sharing = new Sharing($app['user'], $fileManager->getRelativePath($path));
        $app['orm.em']->persist($sharing);
        $app['orm.em']->flush();

        return [$app->url('listFiles', ['token' => $sharing->getToken(), '_prefixId' => 'sharings'])];
    }

    public function removeSharing(Application $app, $token)
    {
        $app['orm.repo.sharing']->delete($token);
        return [];
    }
}
