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

    public function paginate(int $limit, int $offset, array $criteria = [], array $sort = []): Paginator
    {
        $qb = $this->getQueryBuilderForPagination();

        if (count($criteria) > 0) {
            $qb->where($this->getEntityAlias().'.'.$criteria[0].' = :'.$criteria[0]);
            $qb->setParameter($criteria[0], $criteria[1]);
        }

        if (count($sort) > 0) {
            foreach ($sort as $field => $order) {
                $qb->addOrderBy($this->getEntityAlias().'.'.$field, $order);
            }
        }

        return $this->paginateQueryBuilder($qb, $limit, $offset);
    }
}
