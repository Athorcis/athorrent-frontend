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
     * @param mixed $id
     * @throws ORMException
     */
    public function delete(mixed $id): void
    {
        $em = $this->getEntityManager();
        $entity = $em->getReference($this->getEntityName(), $id);

        $em->remove($entity);
        $em->flush();
    }
}
