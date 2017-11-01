<?php

namespace Athorrent\Controllers;

use Athorrent\Dtabase\Entity\Sharing;
use Athorrent\Database\Entity\User;
use Athorrent\Database\Type\UserRole;
use Athorrent\Routing\AbstractController;
use Athorrent\View\PaginatedView;
use Athorrent\View\View;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class UserController extends AbstractController
{
    protected function getRouteDescriptors()
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
            return $app->notify('error', 'error.usernameOrPasswordEmpty');
        }

        if ($app['user_manager']->userExists($username)) {
            return $app->notify('error', 'error.usernameAlreadyUsed');
        }

        if (!in_array($role, UserRole::$values)) {
            $app->abort(400, 'error.roleInvalid');
        }

        $app['user_manager']->createUser($username, $password, $role);

        return $app->redirect('listUsers');
    }

    public function removeUser(Application $app, Request $request)
    {
        $id = $request->request->get('userId');

        if ($id && $app['user_manager']->deleteUserById($id)) {
            return [];
        }

        $app->abort(500, 'error.cannotRemoveUser');
    }
}
