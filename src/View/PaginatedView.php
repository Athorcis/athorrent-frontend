<?php

declare(strict_types=1);

namespace Athorrent\View;

use Athorrent\Database\Repository\PaginableRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaginatedView extends View
{
    /**
     * @param PaginableRepositoryInterface<mixed> $entityRepository
     * @param array{}|array{string, mixed} $criteria
     * @param array<string, 'ASC'|'DESC'> $sort
     */
    public function __construct(Request $request, PaginableRepositoryInterface $entityRepository, int $countPerPage, array $criteria = [], array $sort = [])
    {
        $page = $request->query->getInt('page', 1);

        if ($page < 1) {
            throw new BadRequestHttpException();
        }

        $offset = $countPerPage * ($page - 1);

        $paginator = $entityRepository->paginate($countPerPage, $offset, $criteria, $sort);

        $count = count($paginator);

        if ($offset >= $count && $count > 0) {
            throw new NotFoundHttpException();
        }

        parent::__construct(ViewType::Page, [
            'action' => $request->attributes->get('_action'),
            'pagination' => [
                'entities' => iterator_to_array($paginator),
                'lastPage' => ceil($count / $countPerPage),
                'page' => $page
            ]
        ]);
    }
}
