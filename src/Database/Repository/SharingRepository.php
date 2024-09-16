<?php

namespace Athorrent\Database\Repository;

use Athorrent\Database\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class SharingRepository extends EntityRepository implements DeletableRepositoryInterface, PaginableRepositoryInterface
{
    use DeletableRepositoryTrait;
    use PaginableRepositoryTrait;

    protected function getEntityAlias(): string
    {
        return 's';
    }

    protected function createQueryBuilderByUserAndRoot(User $user, $root): QueryBuilder
    {
        $qb = $this->createQueryBuilder($this->getEntityAlias());

        $qb->where(
            $qb->expr()->eq('s.user', ':user'),
            $qb->expr()->orX(
                $qb->expr()->eq('s.path', ':path'),
                $qb->expr()->like('s.path', ':root')
            )
        );

        $qb->setParameter('user', $user);
        $qb->setParameter('path', $root);
        $qb->setParameter('root', $root . '/%');

        return $qb;
    }

    public function findByUserAndRoot(User $user, $root)
    {
        return $this->createQueryBuilderByUserAndRoot($user, $root)

        ->select(['s', 'LENGTH(s.path) AS HIDDEN l'])
        ->orderBy('l')

        ->getQuery()->execute();
    }

    public function deleteByUserAndRoot(User $user, $root)
    {
        return $this->createQueryBuilderByUserAndRoot($user, $root)

        ->delete()

        ->getQuery()->execute();
    }
}
