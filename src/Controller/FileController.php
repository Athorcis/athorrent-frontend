<?php

declare(strict_types=1);

namespace Athorrent\Controller;

use Athorrent\Database\Repository\SharingRepository;
use Athorrent\Filesystem\Requirements;
use Athorrent\Filesystem\UserFilesystemEntry;
use Athorrent\UserVisibleException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/user/files', name: 'files')]
class FileController extends AbstractFileController
{
    #[Route(path: '/', methods: 'POST', options: ['expose' => true])]
    public function addFile(Request $request, #[Requirements(dir: true)] UserFilesystemEntry $rootEntry)
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('file');

        $rootPath = $rootEntry->getRealPath();
        $relativePath = $request->request->get('relativePath');

        $path = Path::makeAbsolute($relativePath, $rootPath);

        if (!Path::isBasePath($rootPath, $path)) {
            throw new AccessDeniedHttpException();
        }

        if (file_exists($path) &&  $request->request->get('overwrite') !== 'true') {
            throw new UserVisibleException('error.fileExists');
        }

        $dirPath = dirname($path);

        $dirEntry = $rootEntry->getFilesystem()->getEntry($dirPath);

        if ($dirEntry->isWritable()) {
            $fs = new Filesystem();
            $fs->mkdir($dirPath);
        }

        $file->move($dirPath, basename($relativePath));

        return [];
    }

    #[Route(path: '/', methods: 'DELETE', options: ['expose' => true])]
    public function removeFile(#[Requirements(path: true)] UserFilesystemEntry $entry, SharingRepository $sharingRepository): array
    {
        if ($entry->isRoot()) {
            throw $this->createNotFoundException();
        }

        if (!$entry->isDeletable()) {
            throw $this->createAccessDeniedException();
        }

        $entry->remove();

        $sharingRepository->deleteByUserAndRoot($entry->getOwner(), $entry->getPath());

        return [];
    }
}
