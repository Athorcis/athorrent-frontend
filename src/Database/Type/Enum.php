<?php

namespace Athorrent\Database\Type;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

abstract class Enum extends Type
{
    abstract public function getValues();

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return "ENUM('" . implode("', '", $this->getValues()) . "')";
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!in_array($value, $this->getValues())) {
            throw new \InvalidArgumentException('Invalid status');
        }

        return $value;
    }

    public function getName()
    {
        return get_class($this);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
