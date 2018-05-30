<?php

namespace Athorrent\Command;

use Athorrent\Security\UserManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateUserCommand extends \Symfony\Component\Console\Command\Command
{
    protected $userManager;

    public function __construct(UserManager $userManager)
    {
        parent::__construct();
        $this->userManager = $userManager;
    }

    protected function configure()
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $role = $input->getArgument('role');

        $this->userManager->createUser($username, $password, $role);
    }
}
