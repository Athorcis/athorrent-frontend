<?php

namespace Athorrent\Controllers;

use Athorrent\Filesystem\SharedFilesystem;
use Silex\Application;

class SharingFileController extends AbstractFileController
{
    protected function getFilesystem(Application $app)
    {
        $token = $app['request_stack']->getCurrentRequest()->attributes->get('token');
        $sharing = $app['orm.repo.sharing']->findOneBy(['token' => $token]);

        if ($sharing === null) {
            $app->abort(404, 'error.sharingNotFound');
        }

        return new SharedFilesystem($app, $sharing);
    }
}
