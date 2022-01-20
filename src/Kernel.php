<?php

namespace Athorrent;

use Athorrent\Process\CommandProcess;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot()
    {
        parent::boot();

        if (!defined('BIN_DIR')) {
            define('BIN_DIR', $this->getProjectDir() . '/bin');
            define('VAR_DIR', $this->getProjectDir() . '/var');
            define('USER_DIR', VAR_DIR . '/user');

            CommandProcess::setConsolePath(BIN_DIR . '/console');
        }
    }
}
