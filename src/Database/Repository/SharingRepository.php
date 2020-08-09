<?php

namespace Athorrent\Database\Repository;

use Athorrent\Database\Entity\User;
use Doctrine\ORM\EntityRepository;

class SharingRepository extends EntityRepository implements DeletableRepositoryInterface, PaginableRepositoryInterface
{
    use DeletableRepositoryTrait;
    use PaginableRepositoryTrait;

    protected function getEntityAlias(): string
    {
        return 's';
    }

    protected function createQueryBuilderByUserAndRoot(User $user, $root): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder($this->getEntityAlias());

        $qb->where(
            $qb->expr()->eq('s.user', ':user'),
            $qb->expr()->orX(
                $qb->expr()->eq('s.path', ':path'),
                $qb->expr()->like('s.path', ':root')
            )
        );

        $qb->setParameters([
            'user' => $user,
            'path' => $root,
            'root' => $root . '/%'
        ]);

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
