<?php

namespace MSDev\DoctrineFileMakerDriverBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use AppBundle\Entity\Thing;

/**
 * WebContent
 *
 * @ORM\Table(name="WebContent")
 * @ORM\Entity(repositoryClass="MSDev\DoctrineFileMakerDriverBundle\Repository\WebContentRepository")
 */
class WebContent
{
    /**
     * @var int
     * @ORM\Column(name="ID", type="string")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="MSDev\DoctrineFileMakerDriver\FMIdentityGenerator")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="Content", type="text")
     */
    private $content;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent() {
        return $this->content;
    }
}
