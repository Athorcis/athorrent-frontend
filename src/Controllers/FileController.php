<?php

namespace Athorrent\Controllers;

use Athorrent\Utils\FileManager;
use Symfony\Component\HttpFoundation\Request;

class FileController extends AbstractFileController
{
    protected static $actionPrefix = 'files_';

    protected static $routePattern = '/user/files';

    protected function getArguments(Request $request)
    {
        $arguments = parent::getArguments($request);

        array_unshift($arguments, FileManager::getByUser($this->getUserId()));

        return $arguments;
    }
}
