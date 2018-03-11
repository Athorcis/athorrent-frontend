<?php

namespace Athorrent\Controller;

use Athorrent\Filesystem\FilesystemInterface;
use Athorrent\Filesystem\SharedFilesystem;
use Silex\Application;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SharingFileController extends AbstractFileController
{
    /**
     * @param Application $app
     * @return SharedFilesystem
     */
    protected function getFilesystem(Application $app): FilesystemInterface
    {
        $token = $app['request_stack']->getCurrentRequest()->attributes->get('token');
        $sharing = $app['orm.repo.sharing']->findOneBy(['token' => $token]);

        if ($sharing === null) {
            throw new NotFoundHttpException('error.sharingNotFound');
        }

        return new SharedFilesystem($app['torrent_manager']($sharing->getUser()), $app['user'], $sharing);
    }
}
