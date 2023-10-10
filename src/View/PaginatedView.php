<?php

namespace Athorrent\View;

use Athorrent\Database\Repository\PaginableRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaginatedView extends View
{
    public function __construct(Request $request, PaginableRepositoryInterface $entityRepository, $countPerPage, array $criteria = [])
    {
        if ($request->query->has('page')) {
            $page = $request->query->get('page');

            if (!is_numeric($page) || $page < 1) {
                throw new HttpException(400);
            }
        } else {
            $page = 1;
        }

        $offset = $countPerPage * ($page - 1);

        $paginator = $entityRepository->paginate($countPerPage, $offset, $criteria);

        $count = count($paginator);

        if ($offset >= $count && $count > 0) {
            throw new HttpException(404);
        }

        $entities = iterator_to_array($paginator);
        $lastPage = ceil($count / $countPerPage);

        parent::__construct([
            'action' => $request->attributes->get('_action'),
            'pagination' => [
                'entities' => $entities,
                'lastPage' => $lastPage,
                'page' => $page
            ]
        ]);
    }
}
