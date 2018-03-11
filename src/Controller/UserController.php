<?php

namespace Athorrent\Controller;

use Athorrent\Database\Type\UserRole;
use Athorrent\Notification\ErrorNotification;
use Athorrent\Notification\SuccessNotification;
use Athorrent\Routing\AbstractController;
use Athorrent\View\PaginatedView;
use Athorrent\View\View;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class UserController extends AbstractController
{
    public function getRouteDescriptors()
    {
        return [
            ['GET', '/', 'listUsers'],

            ['GET', '/add', 'addUser'],
            ['POST', '/add', 'saveUser'],

            ['POST', '/remove', 'removeUser', 'ajax']
        ];
    }

    public function listUsers(Application $app, Request $request)
    {
        return new PaginatedView($request, $app['orm.repo.user'], 10);
    }

    public function addUser()
    {
        return new View(['roleList' => UserRole::$values]);
    }

    public function saveUser(Application $app, Request $request)
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        $role = $request->request->get('role');

        if (empty($username) || empty($password) || empty($role)) {
            return new ErrorNotification('error.usernameOrPasswordEmpty');
        }

        if ($app['user_manager']->userExists($username)) {
            return new ErrorNotification('error.usernameAlreadyUsed');
        }

        $app['user_manager']->createUser($username, $password, $role);

        return new SuccessNotification('user successfully updated', 'listUsers');
    }

    public function removeUser(Application $app, Request $request)
    {
        $id = $request->request->get('userId');

        if ($id && $app['user_manager']->deleteUserById($id)) {
            return [];
        }

        throw new \Exception('error.cannotRemoveUser');
    }
}
