<?php

namespace Athorrent\Controller;

use Athorrent\Database\Entity\Sharing;
use Athorrent\Database\Repository\SharingRepository;
use Athorrent\Filesystem\UserFilesystemEntry;
use Athorrent\View\PaginatedView;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(path: '/user/sharings', name: 'sharings')]
class SharingController extends AbstractController
{
    protected EntityManagerInterface $entityManager;

    protected SharingRepository $sharingRepository;

    public function __construct(EntityManagerInterface $entityManager, SharingRepository $sharingRepository)
    {
        $this->entityManager = $entityManager;
        $this->sharingRepository = $sharingRepository;
    }

    /**
     *
     * @param Request $request
     * @return PaginatedView
     */
    #[Route(path: '/', methods: 'GET')]
    public function listSharings(Request $request): PaginatedView
    {
        return new PaginatedView($request, $this->sharingRepository, 10, ['user', $this->getUser()]);
    }

    /**
     *
     * @param UserFilesystemEntry $entry
     * @return array
     */
    #[Route(path: '/', methods: 'POST', options: ['expose' => true])]
    #[ParamConverter('entry', options: ['path' => true])]
    public function addSharing(UserFilesystemEntry $entry): array
    {
        if (!$entry->exists()) {
            throw new FileNotFoundException();
        }

        $sharing = new Sharing($this->getUser(), $entry->getPath());
        $this->entityManager->persist($sharing);
        $this->entityManager->flush();

        return [$this->generateUrl('listFiles', ['token' => $sharing->getToken(), '_prefixId' => 'sharings'], UrlGeneratorInterface::ABSOLUTE_URL)];
    }

    /**
     *
     * @param string $token
     * @return array
     *
     * @throws ORMException
     */
    #[Route(path: '/{token}', methods: 'DELETE', options: ['expose' => true])]
    public function removeSharing(string $token): array
    {
        $this->sharingRepository->delete($token);
        return [];
    }
}
