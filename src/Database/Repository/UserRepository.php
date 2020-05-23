<?php

namespace Athorrent\Database\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class UserRepository extends EntityRepository implements DeletableRepositoryInterface, PaginableRepositoryInterface
{
    use DeletableRepositoryTrait;
    use PaginableRepositoryTrait {
        paginateQueryBuilder as paginateQueryBuilderOriginal;
    }

    protected function getEntityAlias(): string
    {
        return 'u';
    }

    protected function paginateQueryBuilder(QueryBuilder $queryBuilder, $limit, $offset): \Doctrine\ORM\Tools\Pagination\Paginator
    {
        $queryBuilder->addSelect('uhr');
        $queryBuilder->join('u.hasRoles', 'uhr');

        return $this->paginateQueryBuilderOriginal($queryBuilder, $limit, $offset);
    }
}
