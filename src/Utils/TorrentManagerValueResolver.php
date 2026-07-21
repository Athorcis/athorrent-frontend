<?php

declare(strict_types=1);

namespace Athorrent\Utils;

use Athorrent\Database\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TorrentManagerValueResolver implements ValueResolverInterface
{
    public function __construct(protected TokenStorageInterface $tokenStorage, protected TorrentManagerFactory $torrentManagerFactory)
    {
    }

    /** @return list<TorrentManagerInterface> */
    public function resolve(Request $request, ArgumentMetadata $argument): array
    {
        $type = $argument->getType();

        if ($type === null || !is_a($type, TorrentManagerInterface::class, true)) {
            return [];
        }

        $token = $this->tokenStorage->getToken();

        if ($token instanceof TokenInterface) {
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
