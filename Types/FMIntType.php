<?php

namespace MSDev\DoctrineFileMakerDriverBundle\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class FMIntType extends Type
{
    protected $name = 'fmint';

    public function getName(): string
    {
        return $this->name;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return $this->name;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    /**
     * Converts the FileMaker value to an int, returning null for any value
     * that cannot be parsed (e.g. "?" or other corrupt data).
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?int
    {
        if (empty($value)) {
            return null;
        }

        $f = filter_var($value, FILTER_VALIDATE_FLOAT);
        return $f === false ? null : (int) $f;
    }
}
