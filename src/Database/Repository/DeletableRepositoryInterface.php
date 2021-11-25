<?php

namespace Athorrent\Database\Repository;

use Doctrine\ORM\Exception\ORMException;

interface DeletableRepositoryInterface
{
    /**
     * @param mixed $id
     * @throws ORMException
     */
    public function delete($id);
}
