<?php

namespace Athorrent\Controllers;

use Athorrent\Entity\Sharing;
use Athorrent\Entity\User;
use Athorrent\Entity\UserRole;
use Athorrent\Utils\UserProvider;
use Symfony\Component\HttpFoundation\Request;

class UserController extends AbstractController {
    protected static $actionPrefix = 'users_';

    protected static $routePattern = '/administration/users';

    protected static function buildRoutes() {
        $routes = parent::buildRoutes();

        $routes[] = array('GET', '/', 'listUsers');

        $routes[] = array('GET', '/add', 'addUser');
        $routes[] = array('POST', '/add', 'saveUser');

        return $routes;
    }

    protected static function buildAjaxRoutes() {
        $routes = parent::buildAjaxRoutes();

        $routes[] = array('POST', '/remove', 'removeUser');

        return $routes;
    }

    protected function listUsers(Request $request) {
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
            $this->abort(404);
        }

        $lastPage = ceil($total / $usersPerPage);

        return $this->render(array (
            'users' => $users,
            'page' => $page,
            'lastPage' => $lastPage
        ));
    }

    protected function addUser(Request $request) {
        return $this->render(array (
            'roleList' => UserRole::$list
        ));
    }

    protected function saveUser(Request $request) {
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        $role = $request->request->get('role');

        if (!empty($username) && !empty($password) && !empty($role)) {
            if (User::exists($username)) {
                return $this->forward('addUser', array('error' => 'error.usernameAlreadyUsed'));
            }

            if (!in_array($role, UserRole::$list)) {
                $this->abort(400, 'error.roleNotSpecified');
            }

            $user = new User(null, $username);
            $user->setRawPassword($password);
            $user->save();

            $userRole = new UserRole($user->getUserId(), $role);
            $userRole->save();
        } else {
            return $this->forward('addUser');
        }

        return $this->redirect('listUsers');
    }

    protected function removeUser(Request $request) {
        $userId = $request->request->get('userId');

        if (!empty($userId)) {
            if (User::deleteByUserId($userId)) {
                Sharing::deleteByUserId($userId);
                return $this->success();
            }
        }

        $this->abort(500, 'error.cannotRemoveUser');
    }
}

?>
