<?php

declare(strict_types=1);

namespace Athorrent\Database\Repository;

use Doctrine\ORM\Exception\ORMException;

interface DeletableRepositoryInterface
{
    /**
     * @throws ORMException
     */
    public function delete(mixed $id);
}
