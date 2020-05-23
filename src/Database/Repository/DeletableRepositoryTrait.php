<?php

namespace Athorrent\Database\Repository;

use Doctrine\ORM\EntityManagerInterface;

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
     * @throws \Doctrine\ORM\ORMException
     */
    public function delete($id): void
    {
        $em = $this->getEntityManager();
        $entity = $em->getReference($this->getEntityName(), $id);

        $em->remove($entity);
        $em->flush();
    }
}
