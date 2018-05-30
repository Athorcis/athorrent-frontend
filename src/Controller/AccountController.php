<?php

namespace Athorrent\Controller;

use Athorrent\Notification\ErrorNotification;
use Athorrent\Notification\SuccessNotification;
use Athorrent\Security\UserManager;
use Athorrent\View\View;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user/account", name="account")
 */
class AccountController extends Controller
{
    protected $userManager;

    protected $entityManager;

    public function __construct(UserManager $userManager, EntityManagerInterface $entityManager)
    {
        $this->userManager = $userManager;
        $this->entityManager = $entityManager;
    }

    /**
     * @Method("GET")
     * @Route("/")
     */
    public function editAccount()
    {
        return new View();
    }

    /**
     * @Method("PUT")
     * @Route("/")
     *
     * @param Request $request
     * @return ErrorNotification|SuccessNotification
     */
    public function saveAccount(Request $request)
    {
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
