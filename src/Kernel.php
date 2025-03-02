<?php

namespace Athorrent;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private ?string $projectDir = null;

    public function boot(): void
    {
        parent::boot();

        if (!defined('USER_ROOT_DIR')) {
            define('USER_ROOT_DIR', $this->getProjectDir() . '/var' . '/user');
        }
    }

    public function getProjectDir(): string
    {
        if (null === $this->projectDir) {
            $this->projectDir = dirname(__DIR__);
        }

        return $this->projectDir;
    }
}
