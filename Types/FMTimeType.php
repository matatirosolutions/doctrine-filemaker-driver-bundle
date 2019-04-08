<?php


namespace MSDev\DoctrineFileMakerDriverBundle\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;

/**
 *
 *
 */
class FMTimeType extends Type
{
    protected $name = 'fmtime';

    public function getName()
    {
        return $this->name;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $this->name;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (is_null($value)) {
            return $value;
        }

        if(is_int($value) && $value > 0) {
            return date('H:i', $value);
        }

        if($value instanceof \DateTime) {
            return $value->format('H:i');
        }


        throw ConversionException::conversionFailed(var_export($value, true), $this->name);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (empty($value)) {
            return null;
        }

        $date = \DateTime::createFromFormat('H:i', $value);
        if($date && $date->format('H:i') === $value) {
            return $date;
        }

        throw ConversionException::conversionFailed(var_export($value, true), $this->name);
    }
}