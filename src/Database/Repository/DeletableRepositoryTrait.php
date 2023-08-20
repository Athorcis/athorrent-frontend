<?php

namespace Athorrent\Database\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;

trait DeletableRepositoryTrait
{
    /**
     * @return EntityManagerInterface
     */
    abstract protected function getEntityManager();

    /**
     * @return string
     */
    abstract protected function getEntityName();

    /**
     * @throws ORMException
     */
    public function delete(mixed $id): void
    {
        $em = $this->getEntityManager();
        $entity = $em->getReference($this->getEntityName(), $id);

        assert($entity !== null, 'failed to acquire reference');

        $em->remove($entity);
        $em->flush();
    }
}
