<?php

namespace Athorrent\Utils;

use Athorrent\Database\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TorrentManagerValueResolver implements ValueResolverInterface
{
    public function __construct(protected TokenStorageInterface $tokenStorage, protected TorrentManagerFactory $torrentManagerFactory)
    {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): array
    {
        if (!is_a($argument->getType(), TorrentManager::class, true)) {
            return [];
        }

        $token = $this->tokenStorage->getToken();

        if ($token) {
            $user = $token->getUser();

            if ($user instanceof User) {
                return [
                    $this->torrentManagerFactory->create($user)
                ];
            }
        }

        return [];
    }
}
