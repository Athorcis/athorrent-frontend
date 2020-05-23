<?php

namespace Athorrent\Utils;

use Athorrent\Database\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TorrentManagerConverter implements ParamConverterInterface
{
    protected $tokenStorage;

    protected $torrentManagerFactory;

    public function __construct(TokenStorageInterface $tokenStorage, TorrentManagerFactory $torrentManagerFactory)
    {
        $this->tokenStorage = $tokenStorage;
        $this->torrentManagerFactory = $torrentManagerFactory;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        $token = $this->tokenStorage->getToken();

        if ($token) {
            $user = $token->getUser();

            if ($user instanceof User) {
                $torrentManager = $this->torrentManagerFactory->create($user);
                $request->attributes->set($configuration->getName(), $torrentManager);
            }
        }
    }

    public function supports(ParamConverter $configuration)
    {
        return $configuration->getClass() === TorrentManager::class;
    }
}
