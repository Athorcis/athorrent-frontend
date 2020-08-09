<?php

namespace Athorrent\Database\Repository;

interface PaginableRepositoryInterface
{
    public function paginate(int $limit, int $offset);

    public function paginateBy(array $criteria, int $limit, int $offset);
}
