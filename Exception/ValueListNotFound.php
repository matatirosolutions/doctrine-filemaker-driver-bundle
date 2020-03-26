<?php


namespace MSDev\DoctrineFileMakerDriverBundle\Exception;

use Exception;

class ValueListNotFound extends Exception
{

    protected $list;

    public function __construct(string $list)
    {
        $this->list = $list;
        parent::__construct("There is no valuelist named {$list}.");
    }

    public function getList(): string
    {
        return $this->list;
    }
}