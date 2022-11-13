<?php

namespace MSDev\DoctrineFileMakerDriverBundle\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;


class FMHTMLType extends Type
{
    protected $name = 'fmhtml';

    public function getName(): string
    {
        return $this->name;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    /**
     * @return mixed
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        return html_entity_decode($value);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return false;
    }

}
