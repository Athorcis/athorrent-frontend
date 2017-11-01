<?php

namespace Athorrent\Database\Repository;

trait DeletableTrait
{
    public function delete($id)
    {
        $em = $this->getEntityManager();
        $entity = $em->getReference($this->getEntityName(), $id);

        $em->remove($entity);
        $em->flush();
    }
}
