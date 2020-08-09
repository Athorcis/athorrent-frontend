<?php

namespace Athorrent\Controller;

use Athorrent\Database\Entity\User;
use Athorrent\Notification\ErrorNotification;
use Athorrent\Notification\SuccessNotification;
use Athorrent\Security\UserManager;
use Athorrent\View\View;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user/account", name="account")
 */
class AccountController extends AbstractController
{
    protected $userManager;

    protected $entityManager;

    public function __construct(UserManager $userManager, EntityManagerInterface $entityManager)
    {
        $this->userManager = $userManager;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/", methods="GET")
     */
    public function editAccount(): View
    {
        return new View();
    }

    /**
     * @Route("/", methods="PUT")
     *
     * @param Request $request
     * @return ErrorNotification|SuccessNotification
     */
    public function saveAccount(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        $username = $request->request->get('username');
        $currentPassword = $request->request->get('current_password');

        if (empty($username) || empty($currentPassword)) {
            return new ErrorNotification('error.usernameOrPasswordEmpty');
        }

        if (!$this->userManager->checkUserPassword($user, $currentPassword)) {
            return new ErrorNotification('error.passwordInvalid');
        }

        if ($user->getUsername() !== $username) {
            if ($this->userManager->userExists($username)) {
                return new ErrorNotification('error.usernameAlreadyUsed');
            }

            $user->setUsername($username);
        }

        $newPassword = $request->request->get('new_password');
        $passwordConfirm = $request->request->get('password_confirm');

        if (!empty($newPassword) || !empty($passwordConfirm)) {
            if ($newPassword !== $passwordConfirm) {
                return new ErrorNotification('error.passwordsDiffer');
            }

            $user->setPlainPassword($newPassword);
            $this->userManager->setUserPassword($user, $newPassword);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new SuccessNotification('account updated successfully');
    }
}
