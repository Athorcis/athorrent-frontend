<?php

namespace Athorrent\Filesystem;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FilesystemConverter implements ParamConverterInterface
{
    private $filesystemFactory;

    public function __construct(FilesystemFactory $filesystemFactory)
    {
        $this->filesystemFactory = $filesystemFactory;
    }

    /**
     * @param UserFilesystem $filesystem
     * @param string|null $path
     * @param array $requirements
     * @return UserFilesystemEntry
     */
    protected function getEntry(UserFilesystem $filesystem, ?string $path, array $requirements = []): UserFilesystemEntry
    {
        static $defaultRequirements = [
            'path' => false,
            'file' => false,
            'dir' => false
        ];

        $requirements = array_merge($defaultRequirements, $requirements);

        if ($path === null) {
            if ($requirements['path']) {
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

        if ($requirements['file'] && !$entry->isFile()) {
            throw new NotFoundHttpException();
        }

        if ($requirements['dir'] && !$entry->isDirectory()) {
            throw new NotFoundHttpException();
        }

        return $entry;
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        if ($request->attributes->has('token')) {
            $filesystem = $this->filesystemFactory->createSharedFilesystem($request->attributes->get('token'));
        } else {
            $filesystem = $this->filesystemFactory->createTorrentFilesystem();
        }

        $entry = $this->getEntry($filesystem, $request->get('path'), $configuration->getOptions());

        $request->attributes->set($configuration->getName(), $entry);

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return is_subclass_of($configuration->getClass(), AbstractFilesystemEntry::class);
    }
}
