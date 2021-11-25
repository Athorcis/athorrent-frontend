<?php

namespace Athorrent\Security;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LoginListener implements EventSubscriberInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        $user->setConnectionDateTime(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public static function getSubscribedEvents(): array
    {
        return [SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin'];
    }
}
