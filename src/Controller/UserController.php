<?php

namespace Athorrent\Controller;

use Athorrent\Database\Entity\User;
use Athorrent\Database\Repository\UserRepository;
use Athorrent\Form\Type\AddUserType;
use Athorrent\Notification\Notification;
use Athorrent\Notification\SuccessNotification;
use Athorrent\Security\UserManager;
use Athorrent\View\PaginatedView;
use Athorrent\View\View;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Exception;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/administration/users', name: 'users')]
class UserController extends AbstractController
{
    public function __construct(protected UserManager $userManager, protected UserRepository $userRepository)
    {
    }

    #[Route(path: '/', methods: 'GET')]
    public function listUsers(Request $request): PaginatedView
    {
        $view = new PaginatedView($request, $this->userRepository, 10);

        $view->addStrings([
            'users.passwordResetConfirmation',
            'users.newPasswordModalTitle',
            'users.deletionConfirmation',
        ]);

        return $view;
    }

    #[Route(path: '/add', methods: ['GET', 'POST'], options: ['delegate_csrf' => true])]
    public function addUser(Request $request, EntityManagerInterface $em): View|Notification
    {
        $user = new User();
        $form = $this->createForm(AddUserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setRoles([$form->get('role')->getData()]);
            $user->setSalt(base64_encode(random_bytes(22)));
            $user->setPort($this->userRepository->getNextAvailablePort());

            $em->persist($user);
            $em->flush();

            return new SuccessNotification('users.add.success', 'listUsers');
        }

        return new View(['form' => $form]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/{userId}', methods: 'POST', options: ['expose' => true])]
    public function resetUserPassword(#[MapEntity(id: 'userId')] User $user, EntityManagerInterface $em): array
    {
        $password = bin2hex(random_bytes(8));
        $user->setPlainPassword($password);
        $em->flush();

        return ['password' => $password];
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/{userId}', methods: 'DELETE', requirements: ['userId' => '\d+'], options: ['expose' => true])]
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
