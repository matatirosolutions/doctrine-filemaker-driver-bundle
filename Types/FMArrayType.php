<?php

namespace MSDev\DoctrineFileMakerDriverBundle\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;

/**
 *
 */
class FMArrayType extends Type
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($column);
    }

    /**
     * @return mixed
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return base64_encode(serialize($value));
    }

    /**
     * @return mixed
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        $value = (is_resource($value)) ? stream_get_contents($value) : $value;
        $val = unserialize(
            base64_decode($value),
            ['allowed_classes' => true]
        );

        if ($val === false && $value !== 'b:0;') {
            throw ConversionException::conversionFailed($value, $this->getName());
        }

        return $val;
    }

    public function getName(): string
    {
        return Types::ARRAY;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
