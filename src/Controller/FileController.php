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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/user/files', name: 'files_')]
class FileController extends AbstractFileController
{
    /** @return array{} */
    #[Route(path: '/', methods: 'POST', options: ['expose' => true])]
    public function addFile(Request $request, #[Requirements(dir: true)] UserFilesystemEntry $rootEntry): array
    {
        /** @var ?UploadedFile $file */
        $file = $request->files->get('file');

        if ($file === null) {
            throw new BadRequestHttpException('missing parameter file');
        }

        $rootPath = $rootEntry->getRealPath();
        $relativePath = $request->request->getString('relativePath');

        $path = Path::makeAbsolute($relativePath, $rootPath);

        if (!Path::isBasePath($rootPath, $path)) {
            throw new AccessDeniedHttpException();
        }

        $dirPath = dirname($path);

        $dirEntry = $rootEntry->getFilesystem()->getEntry($dirPath);

        if (!$dirEntry->isWritable()) {
            throw new UserVisibleException('error.dirNotWritable');
        }

        $fs = new Filesystem();
        $fs->mkdir($dirPath);

        $overwrite = $request->request->getBoolean('overwrite');

        if (!$overwrite) {
            $handle = @fopen($path, 'x');

            if ($handle === false) {
                if (file_exists($path)) {
                    throw new UserVisibleException('error.fileExists');
                }

                throw new \RuntimeException(sprintf('Failed to create file "%s"', $path));
            }

            fclose($handle);
        }

        $file->move($dirPath, basename($relativePath));

        return [];
    }

    /** @return array{} */
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
