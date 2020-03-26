<?php


namespace MSDev\DoctrineFileMakerDriverBundle\Exception;

use Exception;

class TermNotFound extends Exception
{
    /** @var string */
    protected $list;

    /** @var string|int */
    protected $term;

    /**
     * TermNotFound constructor.
     * @param string $list
     * @param int|string $term
     */
    public function __construct(string $list, $term)
    {
        $this->list = $list;
        $this->term = $term;

        parent::__construct("Unable to find a term with ID {$term} in list {$list}");
    }

    /**
     * @return string
     */
    public function getList(): string
    {
        return $this->list;
    }

    /**
     * @return int|string
     */
    public function getTerm()
    {
        return $this->term;
    }
}