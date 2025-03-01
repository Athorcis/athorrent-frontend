<?php

namespace Athorrent\Backend\Process;

use Athorrent\Database\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface BackendProcessManagerInterface
{
    public function create(User $user): BackendProcessInterface;

    public function clean(User $user): void;

    /**
     * @return BackendProcessInterface[]
     */
    public function listRunningProcesses(): array;

    public function isPersistent(): bool;

    public function supportsUpdate(): bool;

    public function requestUpdate(): void;

    public static function getType(): string;
}
