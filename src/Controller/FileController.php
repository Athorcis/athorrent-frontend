<?php

namespace Athorrent\Controller;

use Athorrent\Filesystem\FilesystemInterface;
use Athorrent\Filesystem\TorrentFilesystem;
use Silex\Application;

class FileController extends AbstractFileController
{
    /**
     * @param Application $app
     * @return TorrentFilesystem
     */
    protected function getFilesystem(Application $app): FilesystemInterface
    {
        return $app['user.fs'];
    }
}
