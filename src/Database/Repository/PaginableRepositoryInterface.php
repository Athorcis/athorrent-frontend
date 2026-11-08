<?php

declare(strict_types=1);

namespace Athorrent\Database\Repository;

use Doctrine\ORM\Tools\Pagination\WindowPage;

/**
 * @template T
 */
interface PaginableRepositoryInterface
{
    /**
     * @param array{}|array{string, mixed} $criteria
     * @param array<string, 'ASC'|'DESC'>  $sort
     * @return WindowPage<T>
     */
    public function paginate(int $limit, int $offset, array $criteria = [], array $sort = []): WindowPage;
}
