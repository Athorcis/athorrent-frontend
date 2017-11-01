<?php

namespace Athorrent\Controllers;

use Athorrent\Utils\FileManager;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class FileController extends AbstractFileController
{
    protected function getFilesystem(Application $app)
    {
        return $app['user.fs'];
    }
}
