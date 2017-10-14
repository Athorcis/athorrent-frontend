<?php

namespace Athorrent\Controllers;

use Athorrent\Entity\User;
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
        return $this->render();
    }

    public function saveAccount(Application $app, Request $request)
    {
        $user = $app['user'];

        $username = $request->request->get('username');
        $currentPassword = $request->request->get('current_password');

        if (empty($username) || empty($currentPassword)) {
            $this->addNotification('error', 'error.usernameOrPasswordEmpty');
            return $this->redirect('editAccount');
        }

        if ($app['security.encoder.digest']->encodePassword($currentPassword, $user->getSalt()) !== $user->getPassword()) {
            $this->addNotification('error', 'error.passwordInvalid');
            return $this->redirect('editAccount');
        }

        if ($user->getUsername() !== $username) {
            if (User::exists($username)) {
                $this->addNotification('error', 'error.usernameAlreadyUsed');
                return $this->redirect('editAccount');
            }

            $user->setUsername($username);
        }

        $newPassword = $request->request->get('new_password');
        $passwordConfirm = $request->request->get('password_confirm');

        if (!empty($newPassword) || !empty($passwordConfirm)) {
            if ($newPassword !== $passwordConfirm) {
                $this->addNotification('error', 'error.passwordsDiffer');
                return $this->redirect('editAccount');
            }

            $user->setRawPassword($newPassword);
        }

        $user->save();

        return $this->redirect('editAccount');
    }
}
