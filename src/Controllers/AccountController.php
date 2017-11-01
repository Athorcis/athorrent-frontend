<?php

namespace Athorrent\Controllers;

use Athorrent\Entity\User;
use Athorrent\Routing\AbstractController;
use Athorrent\View\View;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class AccountController extends AbstractController
{
    protected function getRouteDescriptors()
    {
        return [
            ['GET', '/', 'editAccount'],
            ['POST', '/', 'saveAccount']
        ];
    }

    public function editAccount(Request $request)
    {
        return new View();
    }

    public function saveAccount(Application $app, Request $request)
    {
        $user = $app['user'];

        $username = $request->request->get('username');
        $currentPassword = $request->request->get('current_password');

        if (empty($username) || empty($currentPassword)) {
            return $app->notify('error', 'error.usernameOrPasswordEmpty');
        }

        if (!$app['user_manager']->checkUserPassword($user, $currentPassword)) {
            return $app->notify('error', 'error.passwordInvalid');
        }

        if ($user->getUsername() !== $username) {
            if ($app['user_manager']->userExists($username)) {

                return $app->notify('error', 'error.usernameAlreadyUsed');
            }

            $user->setUsername($username);
        }

        $newPassword = $request->request->get('new_password');
        $passwordConfirm = $request->request->get('password_confirm');

        if (!empty($newPassword) || !empty($passwordConfirm)) {
            if ($newPassword !== $passwordConfirm) {
                return $app->notify('error', 'error.passwordsDiffer');
            }

            $app['user_manager']->setUserPassword($user, $newPassword);
        }

        $app['orm.em']->persist($user);
        $app['orm.em']->flush();

        return $app->redirect('editAccount');
    }
}
