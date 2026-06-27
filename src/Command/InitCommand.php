<?php

declare(strict_types=1);

namespace Athorrent\Command;

use Athorrent\Backend\BackendManagerProxy;
use Athorrent\Database\Entity\User;
use Athorrent\Database\Repository\UserRepository;
use Athorrent\Security\UserManager;
use Athorrent\Utils\TorrentManagerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Attribute\Lazy;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Throwable;

enum ResetMode: string {
    case Full = 'full';
    case Quick = 'quick';
}

#[AsCommand('app:init')]
class InitCommand
{
    public function __construct(
        #[Lazy] private BackendManagerProxy $backendManager,
        #[Lazy] private UserRepository      $userRepository,
        #[Lazy] private EntityManagerInterface $entityManager,
        #[Lazy] private TorrentManagerFactory $torrentManagerFactory,
        #[Lazy] private UserManager $userManager,
    )
    {
    }

    public function __invoke(
        #[Option] ?ResetMode $reset = null,
        #[Option] bool $noUnreachableBackend = false,
    ): int
    {
        $this->backendManager->setAllowTransportExceptions(!$noUnreachableBackend);

        if ($reset) {
            $rootUser = $this->reset($reset);
            $this->init($rootUser?->getClientIp());
        }
        elseif (!$this->doesUserTableExist()) {
            $this->init();
        }

        return Command::SUCCESS;
    }

    private function init(?string $clientIp = null): void
    {
        $this->createSchema();

        $this->userManager->createUser('admin', 'test', 'ROLE_ADMIN', $clientIp);
    }

    private function reset(ResetMode $reset): ?User
    {
        if ($_ENV['APP_ENV'] !== 'test') {
            throw new RuntimeException('Reset can only be run in test environment');
        }

        $rootUser = null;

        if ($reset === ResetMode::Quick) {
            $rootUser = $this->detachAndCleanRootUser();
        }

        $this->backendManager->clear();

        $this->dropSchema();

        $this->removeUserDirectoryContent($reset === ResetMode::Quick);

        return $rootUser;
    }

    protected function getSchemaToolAndMetadata()
    {
        static $cache;

        if ($cache === null) {
            $cache = [
                new SchemaTool($this->entityManager),
                $this->entityManager->getMetadataFactory()->getAllMetadata(),
            ];
        }

        return $cache;
    }

    protected function createSchema()
    {
        [$schemaTool, $metadata] = $this->getSchemaToolAndMetadata();
        $schemaTool->createSchema($metadata);
    }

    protected function dropSchema()
    {
        [$schemaTool, $metadata] = $this->getSchemaToolAndMetadata();
        $schemaTool->dropSchema($metadata);
    }

    protected function doesUserTableExist(): bool
    {
        $connection = $this->entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();
        return $schemaManager->tableExists('user');
    }

    protected function detachAndCleanRootUser()
    {
        try {
            $rootUser = $this->userRepository->find(1);

            if ($rootUser instanceof User) {
                $this->entityManager->detach($rootUser);
                $this->backendManager->detachUser($rootUser);
            }

            $torrentManager = $this->torrentManagerFactory->create($rootUser);

            try {
                $torrents = $torrentManager->getTorrents();
            }
            catch (Throwable) {
                $torrents = [];
            }

            foreach ($torrents as $torrent) {
                $torrentManager->removeTorrent($torrent['hash']);
            }

            $finder = new Finder();
            $finder->depth(0)->in($rootUser->getFilesPath());

            $fs = new Filesystem();
            $fs->remove($finder);
        }
        catch (Throwable) {
            $rootUser = null;
        }

        return $rootUser;
    }

    protected function removeUserDirectoryContent(bool $excludeRootUser): void
    {
        $finder = new Finder();
        $finder->depth(0)->in(USER_ROOT_DIR);

        if ($excludeRootUser) {
            $finder->exclude('1');
        }

        $fs = new Filesystem();
        $fs->remove($finder);
    }
}
