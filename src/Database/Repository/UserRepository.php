<?php

namespace Athorrent\Database\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserRepository extends EntityRepository implements DeletableRepositoryInterface, PaginableRepositoryInterface, UserLoaderInterface, PasswordUpgraderInterface
{
    use DeletableRepositoryTrait;
    use PaginableRepositoryTrait {
        paginateQueryBuilder as paginateQueryBuilderOriginal;
    }

    protected function getEntityAlias(): string
    {
        return 'u';
    }

    protected function paginateQueryBuilder(QueryBuilder $qb, $limit, $offset): Paginator
    {
        $qb->addSelect('uhr');
        $qb->join('u.hasRoles', 'uhr');

        return $this->paginateQueryBuilderOriginal($qb, $limit, $offset);
    }

    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        return $this->findOneBy(['username' => $identifier]);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->flush();
    }

    public function getNextAvailablePort(): int
    {
        $nextAvailablePort = $this->createQueryBuilder('u')
            ->select('MAX(u.port) + 1')
            ->getQuery()
            ->getSingleScalarResult();

        return max(6881, $nextAvailablePort);
    }

}
