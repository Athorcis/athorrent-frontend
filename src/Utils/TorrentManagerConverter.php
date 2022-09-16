<?php

namespace Athorrent\Utils;

use Athorrent\Database\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TorrentManagerConverter implements ParamConverterInterface
{
    public function __construct(protected TokenStorageInterface $tokenStorage, protected TorrentManagerFactory $torrentManagerFactory)
    {
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $token = $this->tokenStorage->getToken();

        if ($token) {
            $user = $token->getUser();

            if ($user instanceof User) {
                $torrentManager = $this->torrentManagerFactory->create($user);
                $request->attributes->set($configuration->getName(), $torrentManager);

                return true;
            }
        }

        return false;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getClass() === TorrentManager::class;
    }
}
