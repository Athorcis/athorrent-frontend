<?php

namespace Athorrent\Controller;

use Athorrent\Notification\ErrorNotification;
use Athorrent\Notification\SuccessNotification;
use Athorrent\Routing\AbstractController;
use Athorrent\View\View;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class AccountController extends AbstractController
{
    public function getRouteDescriptors()
    {
        return [
            ['GET', '/', 'editAccount'],
            ['POST', '/', 'saveAccount']
        ];
    }

    public function editAccount()
    {
        return new View();
    }

    public function saveAccount(Application $app, Request $request)
    {
        $user = $app['user'];

        $username = $request->request->get('username');
        $currentPassword = $request->request->get('current_password');

        if (empty($username) || empty($currentPassword)) {
            return new ErrorNotification('error.usernameOrPasswordEmpty');
        }

        if (!$app['user_manager']->checkUserPassword($user, $currentPassword)) {
            return new ErrorNotification('error.passwordInvalid');
        }

        if ($user->getUsername() !== $username) {
            if ($app['user_manager']->userExists($username)) {
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

            $app['user_manager']->setUserPassword($user, $newPassword);
        }

        $app['orm.em']->persist($user);
        $app['orm.em']->flush();

        return new SuccessNotification('account updated successfully');
    }
}
