<?php

namespace Athorrent\Database\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
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

    protected function paginateQueryBuilder(QueryBuilder $queryBuilder, $limit, $offset): \Doctrine\ORM\Tools\Pagination\Paginator
    {
        $queryBuilder->addSelect('uhr');
        $queryBuilder->join('u.hasRoles', 'uhr');

        return $this->paginateQueryBuilderOriginal($queryBuilder, $limit, $offset);
    }

    public function loadUserByUsername(string $username)
    {
        return $this->findOneBy(['username' => $username]);
    }

    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        $user->setPassword($newEncodedPassword);
        $this->_em->flush($user);
    }
}
