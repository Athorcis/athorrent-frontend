<?php

namespace Athorrent\Database\Repository;

use Doctrine\ORM\Exception\ORMException;

interface DeletableRepositoryInterface
{
    /**
     * @throws ORMException
     */
    public function delete(mixed $id);
}
