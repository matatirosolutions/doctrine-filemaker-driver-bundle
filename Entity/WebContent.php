<?php

namespace MSDev\DoctrineFileMakerDriverBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * WebContent
 *
 * @ORM\Table(name="WebContent")
 * @ORM\Entity(repositoryClass="MSDev\DoctrineFileMakerDriverBundle\Repository\WebContentRepository")
 */
class WebContent implements WebContentInterface
{
    /**
     * @var string
     * @ORM\Column(name="ID", type="string")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="MSDev\DoctrineFileMakerDriver\FMIdentityGenerator")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="Content", type="text")
     */
    private $content;

    public function getId(): string
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
