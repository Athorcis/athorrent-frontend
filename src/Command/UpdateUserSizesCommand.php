<?php

declare(strict_types=1);

namespace Athorrent\Command;

use Athorrent\Database\Entity\User;
use Athorrent\Database\Repository\UserRepository;
use Athorrent\Security\UserManager;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('user:update-sizes', 'Recalculate and store disk usage for users')]
class UpdateUserSizesCommand
{
    public function __construct(
        private readonly UserManager $userManager,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function __invoke(
        OutputInterface $output,
        #[Argument(description: 'Optional user id; omit to update all users')] ?int $userId = null,
    ): int {
        if ($userId === null) {
            $users = $this->userRepository->findAll();
        }
        else {
            $user = $this->userRepository->find($userId);

            if ($user instanceof User) {
                $users = [$user];
            }
            else {
                $output->writeln(sprintf('<error>User %d not found</error>', $userId));
                return Command::FAILURE;
            }
        }

        foreach ($users as $user) {
            $size = $this->userManager->updateSize($user);
            $output->writeln(sprintf('User %d (%s): %d bytes', $user->getId(), $user->getUsername(), $size));
        }

        return Command::SUCCESS;
    }
}
