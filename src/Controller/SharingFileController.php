<?php

namespace Athorrent\Controller;

use Athorrent\Database\Entity\Sharing;
use Athorrent\Filesystem\SharedFilesystem;
use Silex\Application;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SharingFileController extends AbstractFileController
{
    protected function getFilesystem(Application $app)
    {
        $token = $app['request_stack']->getCurrentRequest()->attributes->get('token');
        $sharing = $app['orm.repo.sharing']->findOneBy(['token' => $token]);

        if ($sharing === null) {
            throw new NotFoundHttpException('error.sharingNotFound');
        }

        return new SharedFilesystem($app['torrent_manager']($sharing->getUser()), $app['user'], $sharing);
    }
}
