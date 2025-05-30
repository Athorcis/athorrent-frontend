<?php

namespace Athorrent\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ResetTestsDataCommand extends Command
{
    public function __construct()
    {
        parent::__construct('tests:data:reset');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($_ENV['APP_ENV'] !== 'test') {
            throw new \RuntimeException('This command can only be run in test environment');
        }

        $this->runCommand($output, new ArrayInput([
            'command' => 'doctrine:schema:drop',
            '--force' => true,
        ]));

        // @TODO notify backend manager

        $this->runCommand($output, new ArrayInput([
            'command' => 'doctrine:schema:create',
        ]));

        $this->removeUserDirectoryContent();

        $this->runCommand($output, new ArrayInput([
            'command' => 'user:create',
            'username' => 'admin',
            'password' => 'test',
            'role' => 'ROLE_ADMIN',
        ]));

        return Command::SUCCESS;
    }

    protected function runCommand(OutputInterface $output, ArrayInput $input): int
    {
        $input->setInteractive(false);
        return $this->getApplication()->doRun($input, $output);
    }

    protected function removeUserDirectoryContent()
    {
        $finder = new Finder();
        $finder->depth(0)->in(USER_ROOT_DIR);

        $fs = new Filesystem();
        $fs->remove($finder);
    }
}
