<?php

namespace Athorrent\Controller;

use Athorrent\Database\Type\UserRole;
use Athorrent\Notification\ErrorNotification;
use Athorrent\Notification\SuccessNotification;
use Athorrent\View\PaginatedView;
use Athorrent\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/administration/users", name="users")
 */
class UserController
{
    /**
     * @Method("GET")
     * @Route("/")
     */
    public function listUsers(Application $app, Request $request)
    {
        return new PaginatedView($request, $app['orm.repo.user'], 10);
    }

    /**
     * @Method("GET")
     * @Route("/add")
     */
    public function addUser()
    {
        return new View(['roleList' => UserRole::$values]);
    }

    /**
     * @Method("POST")
     * @Route("/")
     */
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

    /**
     * @Method("DELETE")
     * @Route("/{userId}", requirements={"userId"="\d+"}, options={"expose"=true})
     */
    public function removeUser(Application $app, Request $request, $userId)
    {
        if ($app['user_manager']->deleteUserById($userId)) {
            return [];
        }

        throw new \Exception('error.cannotRemoveUser');
    }
}
