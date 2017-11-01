<?php

namespace Athorrent\Database\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

trait PaginableTrait
{
    protected function getQueryBuilderForPagination()
    {
        return $this->createQueryBuilder($this->getEntityAlias());
    }

    protected function getQueryBuilderForPaginationBy(array $criteria)
    {
        $qb = $this->getQueryBuilderForPagination();

        $qb->where($this->getEntityAlias() . '.' . $criteria[0] . ' = :' . $criteria[0]);
        $qb->setParameter($criteria[0], $criteria[1]);

        return $qb;
    }

    protected function paginateQueryBuilder(QueryBuilder $queryBuilder, $limit, $offset)
    {
        $queryBuilder->setMaxResults($limit);
        $queryBuilder->setFirstResult($offset);

        return new Paginator($queryBuilder->getQuery());
    }

    public function paginate($limit, $offset)
    {
        $queryBuilder = $this->getQueryBuilderForPagination();
        return $this->paginateQueryBuilder($queryBuilder, $limit, $offset);
    }

    public function paginateBy(array $criteria, $limit, $offset)
    {
        $queryBuilder = $this->getQueryBuilderForPaginationBy($criteria);
        return $this->paginateQueryBuilder($queryBuilder, $limit, $offset);
    }
}
