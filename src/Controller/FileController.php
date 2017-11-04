<?php

namespace Athorrent\Controller;

use Silex\Application;

class FileController extends AbstractFileController
{
    protected function getFilesystem(Application $app)
    {
        return $app['user.fs'];
    }
}
