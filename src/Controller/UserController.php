<?php

namespace Athorrent\Controller;

use Athorrent\Database\Entity\User;
use Athorrent\Database\Repository\UserRepository;
use Athorrent\Database\Type\UserRole;
use Athorrent\Notification\ErrorNotification;
use Athorrent\Notification\SuccessNotification;
use Athorrent\Security\UserManager;
use Athorrent\View\PaginatedView;
use Athorrent\View\View;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Exception;
use RuntimeException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/administration/users", name="users")
 */
class UserController extends AbstractController
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
     * @throws Exception
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
     * @Route("/{userId}", methods="POST", options={"expose"=true})
     * @ParamConverter("user", options={"id": "userId"})
     *
     * @param User $user
     * @param UserPasswordHasherInterface $hasher
     * @param EntityManagerInterface $em
     * @return array
     * @throws Exception
     */
    public function resetUserPassword(User $user, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): array
    {
        $password = bin2hex(random_bytes(8));

        $user->setPassword($hasher->hashPassword($user, $password));
        $em->flush($user);

        return ['password' => $password];
    }

    /**
     * @Route("/{userId}", methods="DELETE", requirements={"userId"="\d+"}, options={"expose"=true})
     *
     * @param int $userId
     * @return array
     *
     * @throws Exception
     */
    public function removeUser(int $userId): array
    {
        try {
            $this->userRepository->delete($userId);
        }
        catch (ORMException $exception) {
            throw new RuntimeException('error.cannotRemoveUser', 0, $exception);
        }

        return [];
    }
}
