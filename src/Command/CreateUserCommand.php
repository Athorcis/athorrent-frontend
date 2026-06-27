<?php

declare(strict_types=1);

namespace Athorrent\Command;

use Athorrent\Database\Type\UserRole;
use Athorrent\Security\UserManager;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand('user:create', 'Create a user')]
class CreateUserCommand extends Command
{
    public function __construct(protected UserManager $userManager)
    {
        parent::__construct();
    }

    public function __invoke(
        #[Argument] string $username,
        #[Argument] string $password,
        #[Argument(suggestedValues: [self::class, 'suggestRoles'])] array $roles,
    ): int
    {
        $roles = array_map(UserRole::from(...), $roles);

        $this->userManager->createUser($username, $password, $roles);

        return Command::SUCCESS;
    }
}
