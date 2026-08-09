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
class SharingRepository extends EntityRepository implements PaginableRepositoryInterface
{
    /** @use PaginableRepositoryTrait<Sharing> */
    use PaginableRepositoryTrait;

    /** Prefer '!' over '\' — DQL ESCAPE must be a 1-char string literal. */
    private const string LIKE_ESCAPE = '!';

    protected function getEntityAlias(): string
    {
        return 's';
    }

    protected function createQueryBuilderByUserAndRoot(User $user, string $root): QueryBuilder
    {
        $qb = $this->createQueryBuilder($this->getEntityAlias());
        $platform = $this->getEntityManager()->getConnection()->getDatabasePlatform();

        $escapedRoot = $platform->escapeStringForLike($root, self::LIKE_ESCAPE);
        $escapedPrefix = $platform->escapeStringForLike(
            mb_substr($root, 0, Sharing::PATH_PREFIX_LENGTH),
            self::LIKE_ESCAPE,
        );

        $qb->where(
            $qb->expr()->eq('s.user', ':user'),
            "s.pathPrefix LIKE :pathPrefix ESCAPE '" . self::LIKE_ESCAPE . "'",
            $qb->expr()->orX(
                $qb->expr()->eq('s.path', ':path'),
                "s.path LIKE :root ESCAPE '" . self::LIKE_ESCAPE . "'",
            ),
        );

        $qb->setParameter('user', $user);
        $qb->setParameter('pathPrefix', $escapedPrefix . '%');
        $qb->setParameter('path', $root);
        $qb->setParameter('root', $escapedRoot . '/%');

        return $qb;
    }

    public function deleteByUserAndRoot(User $user, string $root): int
    {
        return $this->createQueryBuilderByUserAndRoot($user, $root)

        ->delete()

        ->getQuery()->execute();
    }
}
