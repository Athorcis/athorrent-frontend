<?php

namespace Athorrent\Command;

use Athorrent\Security\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateUserCommand extends Command
{
    public function __construct(protected UserManager $userManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('user:create')
            ->setDescription('Create a user')
            ->addArgument(
                'username',
                InputArgument::REQUIRED
            )
            ->addArgument(
                'password',
                InputArgument::REQUIRED
            )
            ->addArgument(
                'role',
                InputArgument::REQUIRED
            )
            ->addOption(
                'client-ip',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $role = $input->getArgument('role');
        $clientIp = $input->getOption('client-ip');

        $this->userManager->createUser($username, $password, $role, $clientIp);

        return 0;
    }
}
