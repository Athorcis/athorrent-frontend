<?php

declare(strict_types=1);

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

    /** @return list<UserFilesystemEntry> */
    public function resolve(Request $request, ArgumentMetadata $argument): array
    {
        $type = $argument->getType();

        if ($type === null || !is_a($type, UserFilesystemEntry::class, true)) {
            return [];
        }

        if ($request->attributes->has('id') && $request->attributes->get('_prefixId') === 'sharedFiles_') {
            $filesystem = $this->filesystemFactory->createSharedFilesystem($request->attributes->get('id'));
        } else {
            $filesystem = $this->filesystemFactory->createTorrentFilesystem();
        }

        /** @var list<Requirements> $attributes */
        $attributes = $argument->getAttributes(Requirements::class);
        $path = $request->query->getString('path', $request->request->getString('path'));

        return [$this->getEntry($filesystem, $path, $attributes[0] ?? new Requirements())];
    }
}
