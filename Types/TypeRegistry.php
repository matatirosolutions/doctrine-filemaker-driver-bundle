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
    }

    private function registerType($databaseName, $doctrineName, $className, $compatibleDriverMask = null)
    {
        $this->dbToDoctrine[$databaseName] = [$doctrineName, $compatibleDriverMask];
        $this->doctrineToClass[$doctrineName] = $className;

        return $this;
    }

    public function getDatabaseMapping($connectionDriver)
    {
        $result = [];
        foreach ($this->dbToDoctrine as $databaseName => $row) {
            list($doctrineName, $compatibleDriverMask) = $row;
            if (is_null($compatibleDriverMask) || preg_match($compatibleDriverMask, $connectionDriver)) {
                $result[$databaseName] = $doctrineName;
            }
        }
        return $result;
    }

    public function getDoctrineMapping()
    {
        return $this->doctrineToClass;
    }
}