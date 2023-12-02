<?php

namespace Athorrent\View;

use Athorrent\Database\Repository\PaginableRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaginatedView extends View
{
    public function __construct(Request $request, PaginableRepositoryInterface $entityRepository, $countPerPage, array $criteria = [], array $sort = [])
    {
        $page = $request->query->get('page', 1);

        if (!is_numeric($page) || $page < 1) {
            throw new BadRequestHttpException();
        }

        $offset = $countPerPage * ($page - 1);

        $paginator = $entityRepository->paginate($countPerPage, $offset, $criteria, $sort);

        $count = count($paginator);

        if ($offset >= $count && $count > 0) {
            throw new NotFoundHttpException();
        }

        parent::__construct([
            'action' => $request->attributes->get('_action'),
            'pagination' => [
                'entities' => iterator_to_array($paginator),
                'lastPage' => ceil($count / $countPerPage),
                'page' => $page
            ]
        ]);
    }
}
