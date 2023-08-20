<?php

namespace Athorrent\Filesystem;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class FilesystemValueResolver implements ValueResolverInterface
{
    public function __construct(private FilesystemFactory $filesystemFactory)
    {
    }

    protected function getEntry(UserFilesystem $filesystem, ?string $path, Requirements $requirements): UserFilesystemEntry
    {
        if ($path === null) {
            if ($requirements->path) {
                throw new BadRequestHttpException();
            }

            $path = '';
        }

        try {
            $entry = $filesystem->getEntry($path);
        }
        catch (FileNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }

        if ($requirements->file && !$entry->isFile()) {
            throw new NotFoundHttpException();
        }

        if ($requirements->dir && !$entry->isDirectory()) {
            throw new NotFoundHttpException();
        }

        return $entry;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!is_a($argument->getType(), UserFilesystemEntry::class, true)) {
            return [];
        }

        if ($request->attributes->has('token')) {
            $filesystem = $this->filesystemFactory->createSharedFilesystem($request->attributes->get('token'));
        } else {
            $filesystem = $this->filesystemFactory->createTorrentFilesystem();
        }

        $attributes = $argument->getAttributes(Requirements::class);

        return [$this->getEntry($filesystem, $request->get('path'), $attributes[0] ?? new Requirements())];
    }
}
