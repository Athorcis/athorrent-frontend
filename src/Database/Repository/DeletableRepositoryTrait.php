<?php

namespace Athorrent\Database\Repository;

use Doctrine\ORM\EntityManagerInterface;

trait DeletableRepositoryTrait
{
    /**
     * @return EntityManagerInterface
     */
    abstract function getEntityManager();

    /**
     * @return string
     */
    abstract function getEntityName();

    /**
     * @param mixed $id
     * @throws \Doctrine\ORM\ORMException
     */
    public function delete($id)
    {
        $em = $this->getEntityManager();
        $entity = $em->getReference($this->getEntityName(), $id);

        $em->remove($entity);
        $em->flush();
    }
}
