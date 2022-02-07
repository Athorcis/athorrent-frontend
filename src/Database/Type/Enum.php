<?php

namespace Athorrent\Database\Type;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use InvalidArgumentException;

abstract class Enum extends Type
{
    abstract public function getValues();

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return "ENUM('" . implode("', '", $this->getValues()) . "')";
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!in_array($value, $this->getValues(), true)) {
            throw new InvalidArgumentException('Invalid status');
        }

        return $value;
    }

    public function getName(): string
    {
        return get_class($this);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
