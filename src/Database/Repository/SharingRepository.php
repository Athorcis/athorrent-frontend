<?php

declare(strict_types=1);

namespace Athorrent\Database\Repository;

use Athorrent\Database\Entity\Sharing;
use Athorrent\Database\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends EntityRepository<Sharing>
 * @implements PaginableRepositoryInterface<Sharing>
 */
class SharingRepository extends EntityRepository implements DeletableRepositoryInterface, PaginableRepositoryInterface
{
    use DeletableRepositoryTrait;
    /** @use PaginableRepositoryTrait<Sharing> */
    use PaginableRepositoryTrait;

    protected function getEntityAlias(): string
    {
        return 's';
    }

    protected function createQueryBuilderByUserAndRoot(User $user, string $root): QueryBuilder
    {
        $qb = $this->createQueryBuilder($this->getEntityAlias());

        $qb->where(
            $qb->expr()->eq('s.user', ':user'),
            $qb->expr()->like('s.pathPrefix', ':pathPrefix'),
            $qb->expr()->orX(
                $qb->expr()->eq('s.path', ':path'),
                $qb->expr()->like('s.path', ':root')
            )
        );

        $qb->setParameter('user', $user);
        $qb->setParameter('pathPrefix', mb_substr($root, 0, Sharing::PATH_PREFIX_LENGTH) . '%');
        $qb->setParameter('path', $root);
        $qb->setParameter('root', $root . '/%');

        return $qb;
    }

    /**
     * @return list<Sharing>
     */
    public function findByUserAndRoot(User $user, string $root): array
    {
        return $this->createQueryBuilderByUserAndRoot($user, $root)

        ->select(['s', 'LENGTH(s.path) AS HIDDEN l'])
        ->orderBy('l')

        ->getQuery()->execute();
    }

    public function deleteByUserAndRoot(User $user, string $root): int
    {
        return $this->createQueryBuilderByUserAndRoot($user, $root)

        ->delete()

        ->getQuery()->execute();
    }
}
