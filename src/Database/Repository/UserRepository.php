<?php

namespace Athorrent\Database\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;

class UserRepository extends EntityRepository
{
    use DeletableTrait;
    use PaginableTrait {
        paginateQueryBuilder as paginateQueryBuilderOriginal;
    }

    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    protected function getEntityAlias()
    {
        return 'u';
    }

    protected function paginateQueryBuilder(QueryBuilder $queryBuilder, $limit, $offset)
    {
        $queryBuilder->addSelect('uhr');
        $queryBuilder->join('u.hasRoles', 'uhr');

        return $this->paginateQueryBuilderOriginal($queryBuilder, $limit, $offset);
    }
}
