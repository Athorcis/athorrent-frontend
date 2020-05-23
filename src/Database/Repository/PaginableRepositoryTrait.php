<?php

namespace Athorrent\Database\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

trait PaginableRepositoryTrait
{
    /**
     * @param string $alias
     * @param string $indexBy
     * @return QueryBuilder
     */
    abstract public function createQueryBuilder($alias, $indexBy = null);

    protected function getQueryBuilderForPagination(): QueryBuilder
    {
        return $this->createQueryBuilder($this->getEntityAlias());
    }

    protected function paginateQueryBuilder(QueryBuilder $qb, $limit, $offset): Paginator
    {
        $qb->setMaxResults($limit);
        $qb->setFirstResult($offset);

        return new Paginator($qb->getQuery());
    }

    public function paginate(int $limit, int $offset): Paginator
    {
        $qb = $this->getQueryBuilderForPagination();
        return $this->paginateQueryBuilder($qb, $limit, $offset);
    }

    public function paginateBy(array $criteria, int $limit, int $offset): Paginator
    {
        $qb = $this->getQueryBuilderForPagination();

        $qb->where($this->getEntityAlias() . '.' . $criteria[0] . ' = :' . $criteria[0]);
        $qb->setParameter($criteria[0], $criteria[1]);

        return $this->paginateQueryBuilder($qb, $limit, $offset);
    }
}
