<?php

namespace MSDev\DoctrineFileMakerDriverBundle\Entity;

interface WebContentInterface
{
    public function getId(): string;

    public function getContent(): string;
}