<?php

namespace Athorrent\Controller;

use Athorrent\Database\Repository\UserRepository;
use Athorrent\Form\Type\EditAccountType;
use Athorrent\Notification\Notification;
use Athorrent\Notification\SuccessNotification;
use Athorrent\View\View;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route(path: '/user/account', name: 'account')]
class AccountController extends AbstractController
{
    public function __construct(protected EntityManagerInterface $entityManager, protected UserRepository $userLoader)
    {
    }

    /**
     * Remove the logged-in user from Doctrine then reload the user from the database
     */
    protected function reloadUser(): UserInterface
    {
        $sessionUser = $this->getUser();

        if ($sessionUser === null) {
            throw new RuntimeException('no user found in session');
        }

        $this->entityManager->detach($sessionUser);

        return $this->userLoader->loadUserByIdentifier($sessionUser->getUserIdentifier());
    }

    #[Route(path: '/', methods: ['GET', 'POST'])]
    public function editAccount(Request $request, TokenInterface $token): View|Notification
    {
        // We cannot use the session user directly
        // because if validation fail we have to rollback changes
        // also because the UserPassword uses the session user to check the password
        // and the session user also have to be removed from doctrine or else UniqueEntity constraint fail
        $user = $this->reloadUser();

        $form = $this->createForm(EditAccountType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $token->setUser($user);
            $this->entityManager->flush();

            return new SuccessNotification('account.edit.success');
        }

        return new View(['form' => $form, 'form_label_size' => 3]);
    }
}
