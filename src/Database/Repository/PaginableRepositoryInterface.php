<?php

namespace Athorrent\Database\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;

interface PaginableRepositoryInterface
{
    public function paginate(int $limit, int $offset): Paginator;

    public function paginateBy(array $criteria, int $limit, int $offset): Paginator;
}
