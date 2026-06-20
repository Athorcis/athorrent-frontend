<?php

declare(strict_types=1);

namespace Athorrent\Command;

use Athorrent\Backend\BackendManagerProxy;
use Athorrent\Database\Entity\User;
use Athorrent\Database\Repository\UserRepository;
use Athorrent\Utils\TorrentManagerFactory;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Lazy;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Throwable;

class ResetTestsDataCommand extends Command
{
    public function __construct(
        #[Lazy] private BackendManagerProxy $backendManager,
        #[Lazy] private UserRepository      $userRepository,
        #[Lazy] private EntityManagerInterface $entityManager,
        #[Lazy] private TorrentManagerFactory $torrentManagerFactory,
    )
    {
        parent::__construct('tests:data:reset');
    }

    protected function configure(): void
    {
        $this->addOption('clear-all', null, InputOption::VALUE_REQUIRED, '', true);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($_ENV['APP_ENV'] !== 'test') {
            throw new RuntimeException('This command can only be run in test environment');
        }

        $rootUser = null;

        if (($_ENV['APP_INIT'] ?? 'false') === 'true') {
            $clearAll = true;
        }
        else {
            $clearAll = $input->getOption('clear-all');

            if (!$input->getOption('clear-all')) {
                $rootUser = $this->detachAndCleanRootUser();
            }

            $this->backendManager->clear();
        }

        $this->runCommand($output, new ArrayInput([
            'command' => 'doctrine:schema:drop',
            '--force' => true,
        ]));

        $this->removeUserDirectoryContent(!$clearAll);

        $this->runCommand($output, new ArrayInput([
            'command' => 'doctrine:schema:create',
        ]));

        $this->runCommand($output, new ArrayInput([
            'command' => 'user:create',
            'username' => 'admin',
            'password' => 'test',
            'role' => 'ROLE_ADMIN',
            '--client-ip' => $rootUser?->getClientIp() ?? '',
        ]));

        return Command::SUCCESS;
    }

    protected function runCommand(OutputInterface $output, ArrayInput $input): int
    {
        $input->setInteractive(false);
        return $this->getApplication()->doRun($input, $output);
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
