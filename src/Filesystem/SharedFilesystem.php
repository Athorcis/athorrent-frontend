<?php

namespace Athorrent\Filesystem;

use Athorrent\Database\Entity\Sharing;
use Athorrent\Utils\TorrentManager;
use Silex\Application;

class SharedFilesystem extends UserFilesystem
{
    public function __construct(Application $app, Sharing $sharing)
    {
        parent::__construct($app, $sharing->getUser(), $sharing->getPath(), false);
    }
}
