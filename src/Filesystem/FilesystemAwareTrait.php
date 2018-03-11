<?php

namespace Athorrent\Filesystem;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait FilesystemAwareTrait
{
    /**
     * @param Application $app
     * @return Filesystem
     */
    abstract protected function getFilesystem(Application $app): FilesystemInterface;

    /**
     * @param Request $request
     * @param Application $app
     * @param array $requirements
     * @return FilesystemEntry
     */
    protected function getEntry(Request $request, Application $app, array $requirements = []): FilesystemEntryInterface
    {
        static $defaultRequirements = [
            'path' => false,
            'file' => false,
            'dir' => false
        ];

        $requirements = array_merge($defaultRequirements, $requirements);

        $rawPath = $request->get('path', '');

        if ($requirements['path'] && $rawPath === null) {
            throw new BadRequestHttpException();
        }

        $filesystem = $this->getFilesystem($app);
        $entry = $filesystem->getEntry($rawPath);

        if ($requirements['file'] && !$entry->isFile()) {
            throw new NotFoundHttpException();
        }

        if ($requirements['dir'] && !$entry->isDirectory()) {
            throw new NotFoundHttpException();
        }

        return $entry;
    }
}
