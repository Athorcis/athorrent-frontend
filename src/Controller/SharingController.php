<?php

namespace Athorrent\Controller;

use Athorrent\Database\Entity\Sharing;
use Athorrent\Filesystem\FilesystemAwareTrait;
use Athorrent\Filesystem\FilesystemInterface;
use Athorrent\Filesystem\TorrentFilesystem;
use Athorrent\Routing\AbstractController;
use Athorrent\View\PaginatedView;
use Silex\Application;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SharingController extends AbstractController
{
    use FilesystemAwareTrait;

    /**
     * @param Application $app
     * @return TorrentFilesystem
     */
    protected function getFilesystem(Application $app): FilesystemInterface
    {
        return $app['user.fs'];
    }

    public function getRouteDescriptors()
    {
        return [
            ['GET', '/', 'listSharings'],

            ['POST', '/', 'addSharing', 'ajax'],
            ['DELETE', '/{token}', 'removeSharing', 'ajax']
        ];
    }

    public function listSharings(Application $app, Request $request)
    {
        return new PaginatedView($request, $app['orm.repo.sharing'], 10, ['user', $app['user']]);
    }

    public function addSharing(Application $app, Request $request)
    {
        if (!$request->request->has('path')) {
            throw new BadRequestHttpException();
        }

        $entry = $this->getEntry($request, $app, ['path' => true]);

        if (!$entry->exists()) {
            throw new FileNotFoundException();
        }

        $sharing = new Sharing($app['user'], $entry->getPath());
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
