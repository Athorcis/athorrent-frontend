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

        if ($app['security.encoder.digest']->encodePassword($currentPassword, $user->getSalt()) !== $user->getPassword()) {
            return $app->notify('error', 'error.passwordInvalid');
        }

        if ($user->getUsername() !== $username) {
            if (User::exists($username)) {

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

            $user->setRawPassword($newPassword);
        }

        $user->save();

        return $app->redirect('editAccount');
    }
}
