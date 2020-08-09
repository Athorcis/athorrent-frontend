<?php

namespace Athorrent\Controller;

use Athorrent\Database\Repository\UserRepository;
use Athorrent\Database\Type\UserRole;
use Athorrent\Notification\ErrorNotification;
use Athorrent\Notification\SuccessNotification;
use Athorrent\Security\UserManager;
use Athorrent\View\PaginatedView;
use Athorrent\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/administration/users", name="users")
 */
class UserController
{
    protected $userManager;

    protected $userRepository;

    public function __construct(UserManager $userManager, UserRepository $userRepository)
    {
        $this->userManager = $userManager;
        $this->userRepository = $userRepository;
    }

    /**
     * @Route("/", methods="GET")
     *
     * @param Request $request
     * @return PaginatedView
     */
    public function listUsers(Request $request): PaginatedView
    {
        return new PaginatedView($request, $this->userRepository, 10);
    }

    /**
     * @Route("/add", methods="GET")
     */
    public function addUser(): View
    {
        return new View(['roleList' => UserRole::$values]);
    }

    /**
     * @Route("/", methods="POST")
     *
     * @param Request $request
     * @return ErrorNotification|SuccessNotification
     *
     * @throws \Exception
     */
    public function saveUser(Request $request)
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        $role = $request->request->get('role');

        if (empty($username) || empty($password) || empty($role)) {
            return new ErrorNotification('error.usernameOrPasswordEmpty');
        }

        if ($this->userManager->userExists($username)) {
            return new ErrorNotification('error.usernameAlreadyUsed');
        }

        $this->userManager->createUser($username, $password, $role);

        return new SuccessNotification('user successfully updated', 'listUsers');
    }

    /**
     * @Route("/{userId}", methods="DELETE", requirements={"userId"="\d+"}, options={"expose"=true})
     *
     * @param int $userId
     * @return array
     *
     * @throws \Exception
     */
    public function removeUser(int $userId): array
    {
        if ($this->userManager->deleteUserById($userId)) {
            return [];
        }

        throw new RuntimeException('error.cannotRemoveUser');
    }
}
