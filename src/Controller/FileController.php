<?php

declare(strict_types=1);

namespace Athorrent\Controller;

use Athorrent\Database\Repository\SharingRepository;
use Athorrent\Filesystem\Requirements;
use Athorrent\Filesystem\UserFilesystemEntry;
use Athorrent\UserVisibleException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

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

        if ($request->request->has('dzuuid')) {
            $uploadPath = $this->addFileChunk(
                $request,
                $file,
                $path,
                $overwrite,
                $rootEntry->getOwner()->getId(),
                $fs,
            );

            if ($uploadPath === null) {
                return [];
            }
        }

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

        if (isset($uploadPath)) {
            $fs->rename($uploadPath, Path::join($dirPath, $relativePath));
        }
        else {
            $file->move($dirPath, basename($relativePath));
        }

        return [];
    }

    /**
     * Handles a Dropzone chunked upload request and assembles the file when the last chunk arrives.
     */
    private function addFileChunk(
        Request $request,
        UploadedFile $file,
        string $path,
        bool $overwrite,
        int $userId,
        Filesystem $fs,
    ): ?string {
        $uuid = $request->request->getString('dzuuid');
        $chunkIndex = $request->request->getInt('dzchunkindex');
        $totalChunks = $request->request->getInt('dztotalchunkcount');
        $expectedSize = $request->request->getInt('dztotalfilesize');

        if (!Uuid::isValid($uuid)) {
            throw new BadRequestHttpException('invalid chunk uuid');
        }

        $uuid = Uuid::fromString($uuid)->toRfc4122();

        if ($totalChunks < 1 || $chunkIndex < 0 || $chunkIndex >= $totalChunks) {
            throw new BadRequestHttpException('invalid chunk index');
        }

        if (!$overwrite && file_exists($path)) {
            throw new UserVisibleException('error.fileExists');
        }

        $uploadDir = Path::join(sys_get_temp_dir(), 'athorrent-upload');
        $chunkDir = Path::join($uploadDir, 'chunked', (string) $userId, $uuid);

        $fs->mkdir($chunkDir);

        $file->move($chunkDir, (string) $chunkIndex);

        if ($chunkIndex < $totalChunks - 1) {
            return null;
        }

        for ($i = 0; $i < $totalChunks; ++$i) {
            if (!$fs->exists(Path::join($chunkDir, (string) $i))) {
                throw new BadRequestException(sprintf('Missing chunk %d for upload "%s"', $i, $uuid));
            }
        }

        try {
            $outputPath = Path::join($uploadDir, 'assembled', (string) $userId, $uuid);

            $fs->mkdir(dirname($outputPath));

            return $this->assembleChunks($chunkDir, $outputPath, $totalChunks, $expectedSize);
        } finally {
            $fs->remove($chunkDir);
        }
    }

    private function assembleChunks(
        string $chunkDir,
        string $outputPath,
        int $totalChunks,
        int $expectedSize,
    ): string {
        $out = fopen($outputPath, 'wb');

        if ($out === false) {
            throw new \RuntimeException(sprintf('Failed to open output path "%s"', $outputPath));
        }

        try {
            for ($i = 0; $i < $totalChunks; ++$i) {
                $chunkPath = Path::join($chunkDir, (string) $i);
                $in = fopen($chunkPath, 'rb');

                if ($in === false) {
                    throw new \RuntimeException(sprintf('Failed to read chunk "%s"', $chunkPath));
                }

                try {
                    stream_copy_to_stream($in, $out);
                } finally {
                    fclose($in);
                }
            }
        } catch (\Throwable $e) {
            fclose($out);
            @unlink($outputPath);
            throw $e;
        }

        fclose($out);

        $actualSize = filesize($outputPath);

        if ($actualSize !== $expectedSize) {
            @unlink($outputPath);
            throw new \RuntimeException(sprintf(
                'Assembled size mismatch for "%s": expected %d bytes, got %d',
                $outputPath,
                $expectedSize,
                $actualSize,
            ));
        }

        return $outputPath;
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
