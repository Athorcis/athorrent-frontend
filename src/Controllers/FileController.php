<?php

namespace Athorrent\Controllers;

use Athorrent\Utils\FileManager;
use Symfony\Component\HttpFoundation\Request;

class FileController extends AbstractFileController
{
    protected function getFileManager()
    {
        return FileManager::getByUser($this->getUserId());
    }
}
