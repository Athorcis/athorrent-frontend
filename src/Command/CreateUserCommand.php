<?php

namespace Athorrent\Command;

use Athorrent\Security\SecurityServiceProvider;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateUserCommand extends Command
{
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
        $app = $this->getSilexApplication();

        if (!isset($app['user_manager'])) {
            $app->register(new SecurityServiceProvider());
        }

        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $role = $input->getArgument('role');

        $app['user_manager']->createUser($username, $password, $role);
    }
}
