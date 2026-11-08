<?php

declare(strict_types=1);

namespace Athorrent\Command;

use Athorrent\Backend\BackendManagerProxy;
use Athorrent\Database\Entity\User;
use Athorrent\Database\Repository\UserRepository;
use Athorrent\Security\UserManager;
use Athorrent\Utils\TorrentManagerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Lazy;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

enum ResetMode: string
{
    case Full = 'full';
    case Quick = 'quick';
}

#[AsCommand('app:init')]
readonly class InitCommand
{
    public function __construct(
        private KernelInterface $kernel,
        #[Lazy]
        private BackendManagerProxy $backendManager,
        #[Lazy]
        private UserRepository $userRepository,
        #[Lazy]
        private EntityManagerInterface $entityManager,
        #[Lazy]
        private TorrentManagerFactory $torrentManagerFactory,
        #[Lazy]
        private UserManager $userManager,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option]
        ?ResetMode $reset = null,
        #[Option]
        bool $noUnreachableBackend = false,
    ): int {
        $this->backendManager->setAllowTransportExceptions(!$noUnreachableBackend);

        if ($reset) {
            $rootUser = $this->reset($reset);
            $this->init($io, $rootUser?->getClientIp());
        }
        elseif (!$this->doesUserTableExist()) {
            $this->init($io);
        }

        return Command::SUCCESS;
    }

    private function init(SymfonyStyle $io, ?string $clientIp = null): void
    {
        $this->createSchema();

        $password = in_array($this->kernel->getEnvironment(), ['dev', 'test'], true)
            ? 'test'
            : bin2hex(random_bytes(24));

        $this->userManager->createUser('admin', $password, 'ROLE_ADMIN', $clientIp);

        if ($this->kernel->getEnvironment() === 'prod') {
            $io->success('Created the initial admin account.');
            $io->warning(sprintf('Save this generated password; it will not be shown again: %s', $password));
        }
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

    /**
     * @return array{SchemaTool, list<ClassMetadata<object>>}
     */
    protected function getSchemaToolAndMetadata(): array
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

    protected function createSchema(): void
    {
        [$schemaTool, $metadata] = $this->getSchemaToolAndMetadata();
        $schemaTool->createSchema($metadata);
    }

    protected function dropSchema(): void
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

    protected function detachAndCleanRootUser(): ?User
    {
        try {
            $rootUser = $this->userRepository->find(1);

            if ($rootUser === null) {
                return null;
            }

            $this->entityManager->detach($rootUser);
            $this->backendManager->detachUser($rootUser);

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
        // @phpstan-ignore constant.notFound
        $finder->depth(0)->in(USER_ROOT_DIR);

        if ($excludeRootUser) {
            $finder->exclude('1');
        }

        $fs = new Filesystem();
        $fs->remove($finder);
    }
}
