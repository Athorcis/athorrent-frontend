<?php

namespace Athorrent\Controllers;

use Athorrent\Utils\FileManager;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class FileController extends AbstractFileController
{
    protected function getFileManager(Application $app)
    {
        return FileManager::getByUser($app['user']->getId());
    }
}
