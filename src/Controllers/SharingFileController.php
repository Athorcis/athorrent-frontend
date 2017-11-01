<?php

namespace Athorrent\Controllers;

use Athorrent\Entity\Sharing;
use Athorrent\Utils\FileManager;
use Silex\Application;

class SharingFileController extends AbstractFileController
{

    protected function getFileManager(Application $app)
    {
        $token = $app['request_stack']->getCurrentRequest()->attributes->get('token');
        $sharing = $app['orm.repo.sharing']->findOneBy(['token' => $token]);

        if ($sharing === null) {
            $app->abort(404, 'error.sharingNotFound');
        }

        return FileManager::getBySharing($app['user']->getId(), $sharing);
    }
}
