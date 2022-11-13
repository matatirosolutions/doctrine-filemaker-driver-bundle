<?php


namespace MSDev\DoctrineFileMakerDriverBundle\Types;


class TypeRegistry
{
    private $dbToDoctrine = [];

    private $doctrineToClass = [];

    public function __construct()
    {
        $this->registerType('fmdate', 'fmdate', FMDateType::class);
        $this->registerType('fmdatetime', 'fmdatetime', FMDateTimeType::class);
        $this->registerType('fmtime', 'fmtime', FMTimeType::class);
        $this->registerType('fmhtml', 'fmhtml', FMHTMLType::class);
        $this->registerType('array', 'array', FMArrayType::class);
    }

    private function registerType($databaseName, $doctrineName, $className, $compatibleDriverMask = null): void
    {
        $this->dbToDoctrine[$databaseName] = [$doctrineName, $compatibleDriverMask];
        $this->doctrineToClass[$doctrineName] = $className;

    }

    public function getDatabaseMapping($connectionDriver): array
    {
        $result = [];
        foreach ($this->dbToDoctrine as $databaseName => $row) {
            [$doctrineName, $compatibleDriverMask] = $row;
            if (is_null($compatibleDriverMask) || preg_match($compatibleDriverMask, $connectionDriver)) {
                $result[$databaseName] = $doctrineName;
            }
        }
        return $result;
    }

    public function getDoctrineMapping(): array
    {
        return $this->doctrineToClass;
    }

}
