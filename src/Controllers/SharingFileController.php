<?php

namespace Athorrent\Controllers;

use Athorrent\Entity\Sharing;
use Athorrent\Utils\FileManager;
use Silex\Application;

class SharingFileController extends AbstractFileController
{
    protected function getFileManager(Application $app)
    {
        $sharing = Sharing::loadByToken($app['request_stack']->getCurrentRequest()->attributes->get('token'));

        if (!$sharing) {
            $app->abort(404, 'error.sharingNotFound');
        }

        return FileManager::getBySharing($app['user']->getUserId(), $sharing);
    }
}
