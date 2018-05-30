<?php

namespace Athorrent\Database\Repository;

use Athorrent\Database\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;

class SharingRepository extends EntityRepository implements DeletableRepositoryInterface, PaginableRepositoryInterface
{
    use DeletableRepositoryTrait;
    use PaginableRepositoryTrait;

    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    protected function getEntityAlias()
    {
        return 's';
    }

    protected function createQueryBuilderByUserAndRoot(User $user, $root)
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
