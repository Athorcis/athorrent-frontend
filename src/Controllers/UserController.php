<?php

namespace Athorrent\Controllers;

use Athorrent\Entity\Sharing;
use Athorrent\Entity\User;
use Athorrent\Entity\UserRole;
use Athorrent\Routing\AbstractController;
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

    public function listUsers(Request $request)
    {
        if ($request->query->has('page')) {
            $page = $request->query->get('page');

            if (!is_numeric($page) || $page < 1) {
                $app->abort(400);
            }
        } else {
            $page = 1;
        }

        $usersPerPage = 10;
        $offset = $usersPerPage * ($page - 1);

        $users = User::loadAll($offset, $usersPerPage, $total);

        if ($offset >= $total) {
            $app->abort(404);
        }

        $lastPage = ceil($total / $usersPerPage);

        return new View([
            'users' => $users,
            'page' => $page,
            'lastPage' => $lastPage
        ]);
    }

    public function addUser()
    {
        return new View(['roleList' => UserRole::$list]);
    }

    public function saveUser(Application $app, Request $request)
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        $role = $request->request->get('role');

        if (!empty($username) && !empty($password) && !empty($role)) {
            if (User::exists($username)) {
                return $app->notify('error', 'error.usernameAlreadyUsed');
            }

            if (!in_array($role, UserRole::$list)) {
                $app->abort(400, 'error.roleNotSpecified');
            }

            $user = new User(null, $username);
            $user->setRawPassword($password);
            $user->save();

            $userRole = new UserRole($user->getUserId(), $role);
            $userRole->save();
        } else {
            return $app->notify('error', 'error.usernameOrPasswordEmpty');
        }

        return $app->redirect('listUsers');
    }

    public function removeUser(Request $request)
    {
        $userId = $request->request->get('userId');

        if (!empty($userId)) {
            if (User::deleteByUserId($userId)) {
                UserRole::deleteByUserId($userId);
                Sharing::deleteByUserId($userId);
                return [];
            }
        }

        $app->abort(500, 'error.cannotRemoveUser');
    }
}
