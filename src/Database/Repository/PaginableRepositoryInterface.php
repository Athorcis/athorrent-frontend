<?php

namespace Athorrent\Database\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;

interface PaginableRepositoryInterface
{
    public function paginate(int $limit, int $offset, array $criteria = [], array $sort = []): Paginator;

}
